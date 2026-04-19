-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 05 Nov 2025 pada 04.10
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `serah_terima_db`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `areas`
--

CREATE TABLE `areas` (
  `area_id` int(11) NOT NULL,
  `area_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `areas`
--

INSERT INTO `areas` (`area_id`, `area_name`) VALUES
(1, 'Head Office (HO)'),
(2, 'DC Sibolga'),
(3, 'DC Padang Sidimpuan'),
(4, 'DC Rantau Prapat'),
(5, 'All Access'),
(6, 'DC Siantar'),
(7, 'DC Kabanjahe');

-- --------------------------------------------------------

--
-- Struktur dari tabel `audit_log`
--

CREATE TABLE `audit_log` (
  `log_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `audit_log`
--

INSERT INTO `audit_log` (`log_id`, `user_id`, `action`, `details`, `timestamp`) VALUES
(340, 6, 'LOGIN', 'Login berhasil.', '2025-10-16 02:34:12'),
(341, 1, 'LOGIN', 'Login berhasil.', '2025-11-05 02:38:43'),
(342, 1, 'LOGIN', 'Login berhasil.', '2025-11-05 02:50:21');

-- --------------------------------------------------------

--
-- Struktur dari tabel `documents`
--

CREATE TABLE `documents` (
  `doc_id` int(11) NOT NULL,
  `barcode_id` varchar(20) NOT NULL,
  `sender_user_id` int(11) NOT NULL,
  `receiver_ho_user_id` int(11) DEFAULT NULL,
  `receiver_dc_user_id` int(11) DEFAULT NULL,
  `doc_type` varchar(100) DEFAULT NULL,
  `start_period` date DEFAULT NULL,
  `end_period` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `so_id` int(11) DEFAULT NULL,
  `doc_type_so` varchar(255) DEFAULT NULL,
  `start_period_so` date DEFAULT NULL,
  `end_period_so` date DEFAULT NULL,
  `return_notes` text DEFAULT NULL,
  `status` varchar(50) NOT NULL DEFAULT 'Dikirim ke HO',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `received_at_ho` datetime DEFAULT NULL,
  `check_notes` text DEFAULT NULL,
  `checked_at` datetime DEFAULT NULL,
  `returned_at_ho` datetime DEFAULT NULL,
  `received_at_dc` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `ekspedisi`
--

CREATE TABLE `ekspedisi` (
  `id` int(11) NOT NULL,
  `barcode_id` varchar(20) NOT NULL,
  `jenis_pengiriman` enum('DC_ke_HO','HO_ke_DC') NOT NULL,
  `nomor_resi` varchar(50) NOT NULL,
  `jasa_ekspedisi_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `jasa_ekspedisi`
--

CREATE TABLE `jasa_ekspedisi` (
  `id` int(11) NOT NULL,
  `nama_jasa` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `jasa_ekspedisi`
--

INSERT INTO `jasa_ekspedisi` (`id`, `nama_jasa`) VALUES
(8, 'Anteraja'),
(9, 'Indah Logistik Cargo'),
(3, 'J&T Cargo'),
(2, 'J&T Express'),
(10, 'JET Express'),
(1, 'JNE'),
(4, 'LION PARCEL'),
(5, 'Ninja Xpress'),
(6, 'POS Indonesia'),
(7, 'SICEPAT'),
(11, 'TIKI');

-- --------------------------------------------------------

--
-- Struktur dari tabel `jenis_berkas`
--

CREATE TABLE `jenis_berkas` (
  `id` int(11) NOT NULL,
  `nama_berkas` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `jenis_berkas`
--

INSERT INTO `jenis_berkas` (`id`, `nama_berkas`) VALUES
(1, 'BPK dan EBP'),
(2, 'Laporan'),
(4, 'Meterai'),
(3, 'STNK');

-- --------------------------------------------------------

--
-- Struktur dari tabel `roles`
--

CREATE TABLE `roles` (
  `role_id` varchar(50) NOT NULL,
  `role_name` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `roles`
--

INSERT INTO `roles` (`role_id`, `role_name`) VALUES
('admin_dc', 'Admin DC'),
('admin_ho', 'Admin HO'),
('superadmin', 'Superadmin');

-- --------------------------------------------------------

--
-- Struktur dari tabel `sales_offices`
--

CREATE TABLE `sales_offices` (
  `so_id` int(11) NOT NULL,
  `so_name` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `sales_offices`
--

INSERT INTO `sales_offices` (`so_id`, `so_name`) VALUES
(1, 'SO Panyabungan');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `user_id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role_id` varchar(50) NOT NULL,
  `area_id` int(11) NOT NULL,
  `status` enum('Aktif','Tidak Aktif') NOT NULL DEFAULT 'Aktif',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`user_id`, `username`, `password`, `full_name`, `role_id`, `area_id`, `status`, `created_at`) VALUES
(1, 'abdi', '12345', 'Abdi Hazman', 'superadmin', 5, 'Aktif', '2025-08-26 15:17:34'),
(5, 'febri', '12345', 'Febri', 'admin_dc', 3, 'Aktif', '2025-08-26 15:18:51'),
(6, 'admin', '12345', 'admin', 'admin_ho', 1, 'Aktif', '2025-08-26 15:19:16'),
(7, 'siska', '12345', 'Fransiska', 'admin_ho', 1, 'Aktif', '2025-08-28 03:00:30'),
(8, 'enggan', '12345', 'Enggan Tresia Dachi', 'admin_dc', 2, 'Aktif', '2025-08-28 03:26:08'),
(9, 'dian', '12345', 'Dian Febryanti', 'admin_dc', 4, 'Aktif', '2025-08-28 03:26:33'),
(10, 'ian', '12345', 'Ian Sebastian', 'superadmin', 5, 'Aktif', '2025-08-28 07:11:59'),
(11, 'khairul', '12345', 'Khairul Fauzi', 'admin_dc', 4, 'Aktif', '2025-08-28 16:34:10'),
(15, 'Superadmin', 'poweradmin123', 'Super Administrator', 'superadmin', 5, 'Aktif', '2025-08-31 14:45:45');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `areas`
--
ALTER TABLE `areas`
  ADD PRIMARY KEY (`area_id`);

--
-- Indeks untuk tabel `audit_log`
--
ALTER TABLE `audit_log`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`doc_id`),
  ADD UNIQUE KEY `barcode_id` (`barcode_id`),
  ADD KEY `sender_user_id` (`sender_user_id`),
  ADD KEY `documents_ibfk_2` (`receiver_ho_user_id`),
  ADD KEY `documents_ibfk_3` (`receiver_dc_user_id`),
  ADD KEY `fk_sales_office` (`so_id`);

--
-- Indeks untuk tabel `ekspedisi`
--
ALTER TABLE `ekspedisi`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_resi` (`barcode_id`,`jenis_pengiriman`),
  ADD KEY `jasa_ekspedisi_id` (`jasa_ekspedisi_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indeks untuk tabel `jasa_ekspedisi`
--
ALTER TABLE `jasa_ekspedisi`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nama_jasa` (`nama_jasa`);

--
-- Indeks untuk tabel `jenis_berkas`
--
ALTER TABLE `jenis_berkas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nama_berkas` (`nama_berkas`);

--
-- Indeks untuk tabel `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`role_id`);

--
-- Indeks untuk tabel `sales_offices`
--
ALTER TABLE `sales_offices`
  ADD PRIMARY KEY (`so_id`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`user_id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `role_id` (`role_id`),
  ADD KEY `area_id` (`area_id`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `areas`
--
ALTER TABLE `areas`
  MODIFY `area_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT untuk tabel `audit_log`
--
ALTER TABLE `audit_log`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=343;

--
-- AUTO_INCREMENT untuk tabel `documents`
--
ALTER TABLE `documents`
  MODIFY `doc_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT untuk tabel `ekspedisi`
--
ALTER TABLE `ekspedisi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT untuk tabel `jasa_ekspedisi`
--
ALTER TABLE `jasa_ekspedisi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT untuk tabel `jenis_berkas`
--
ALTER TABLE `jenis_berkas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT untuk tabel `sales_offices`
--
ALTER TABLE `sales_offices`
  MODIFY `so_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `user_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `audit_log`
--
ALTER TABLE `audit_log`
  ADD CONSTRAINT `audit_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Ketidakleluasaan untuk tabel `documents`
--
ALTER TABLE `documents`
  ADD CONSTRAINT `documents_ibfk_1` FOREIGN KEY (`sender_user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `documents_ibfk_2` FOREIGN KEY (`receiver_ho_user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `documents_ibfk_3` FOREIGN KEY (`receiver_dc_user_id`) REFERENCES `users` (`user_id`),
  ADD CONSTRAINT `fk_sales_office` FOREIGN KEY (`so_id`) REFERENCES `sales_offices` (`so_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `ekspedisi`
--
ALTER TABLE `ekspedisi`
  ADD CONSTRAINT `ekspedisi_ibfk_1` FOREIGN KEY (`jasa_ekspedisi_id`) REFERENCES `jasa_ekspedisi` (`id`),
  ADD CONSTRAINT `ekspedisi_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`);

--
-- Ketidakleluasaan untuk tabel `users`
--
ALTER TABLE `users`
  ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`),
  ADD CONSTRAINT `users_ibfk_2` FOREIGN KEY (`area_id`) REFERENCES `areas` (`area_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
