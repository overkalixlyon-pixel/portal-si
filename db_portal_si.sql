-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jul 08, 2026 at 04:02 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_portal_si`
--

-- --------------------------------------------------------

--
-- Table structure for table `tabel_alumni_pengalaman`
--

CREATE TABLE `tabel_alumni_pengalaman` (
  `id` int(11) NOT NULL,
  `alumni_id` int(11) NOT NULL,
  `jabatan` varchar(100) NOT NULL,
  `instansi` varchar(100) NOT NULL,
  `periode` varchar(50) NOT NULL,
  `deskripsi` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tabel_alumni_profil`
--

CREATE TABLE `tabel_alumni_profil` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `nim` varchar(20) NOT NULL,
  `nama_lengkap` varchar(150) NOT NULL,
  `tahun_masuk` int(11) DEFAULT NULL,
  `tahun_lulus` int(11) DEFAULT NULL,
  `angkatan` varchar(10) NOT NULL,
  `usia` int(11) NOT NULL,
  `jalur_masuk` varchar(50) DEFAULT 'Reguler',
  `jabatan_sekarang` varchar(100) DEFAULT 'Belum Bekerja',
  `perusahaan_sekarang` varchar(100) DEFAULT '-',
  `domisili` varchar(100) NOT NULL,
  `linkedin_url` varchar(255) DEFAULT '#',
  `ringkasan_profesional` text DEFAULT NULL,
  `foto_profil` varchar(255) DEFAULT 'default-avatar.png',
  `foto_sampul` varchar(255) DEFAULT 'default-cover.png'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tabel_alumni_profil`
--

INSERT INTO `tabel_alumni_profil` (`id`, `user_id`, `nim`, `nama_lengkap`, `tahun_masuk`, `tahun_lulus`, `angkatan`, `usia`, `jalur_masuk`, `jabatan_sekarang`, `perusahaan_sekarang`, `domisili`, `linkedin_url`, `ringkasan_profesional`, `foto_profil`, `foto_sampul`) VALUES
(2, 3, 'A12.2019.00001', 'Andika Pratama, S.Kom', 2019, 2023, '2019', 25, 'Reguler', 'Account Executive', 'Techarea Digital Agency', 'Semarang, Jawa Tengah', '#', 'Sebagai lulusan Sistem Informasi dengan minat kuat pada perpotongan antara teknologi dan strategi bisnis, saya mendedikasikan diri untuk membantu perusahaan melakukan transformasi digital.', 'alumni1.jpg', 'default-cover.png'),
(3, 4, 'A12.2020.00002', 'Siska Wulandari, S.Kom', 2020, 2024, '2020', 24, 'Reguler', 'IT Support & System Analyst', 'KPP Pratama Salatiga (DJP)', 'Salatiga, Jawa Tengah', '#', 'Memiliki dedikasi tinggi pada ketepatan dan keamanan data. Pemahaman mendalam tentang Sistem Informasi Akuntansi (SIA) dan audit tata kelola IT menjadi modal utama saya.', 'alumni2.jpg', 'default-cover.png'),
(4, 5, 'A12.2021.00003', 'Reza Fahlevi, S.Kom', 2021, 2025, '2021', 23, 'Reguler', 'Data Engineer', 'Fintech & Trading Platform', 'Jakarta Selatan, DKI Jakarta', '#', 'Seorang Data Engineer yang sangat antusias dengan algoritma, arsitektur database, dan pasar finansial XAU/USD.', 'alumni3.jpg', 'default-cover.png');

-- --------------------------------------------------------

--
-- Table structure for table `tabel_alumni_sertifikasi`
--

CREATE TABLE `tabel_alumni_sertifikasi` (
  `id` int(11) NOT NULL,
  `alumni_id` int(11) NOT NULL,
  `nama_sertifikat` varchar(150) NOT NULL,
  `penerbit` varchar(150) NOT NULL,
  `logo_text` varchar(20) NOT NULL,
  `warna_tailwind` varchar(50) DEFAULT 'text-gray-500'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tabel_dosen`
--

CREATE TABLE `tabel_dosen` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `nama_dosen` varchar(150) NOT NULL,
  `jabatan_akademik` varchar(100) NOT NULL,
  `kepakaran` varchar(150) NOT NULL,
  `foto_dosen` varchar(255) NOT NULL,
  `sambutan_teks` text DEFAULT NULL,
  `urutan_tampil` int(11) DEFAULT 2,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tabel_konfigurasi_prodi`
--

CREATE TABLE `tabel_konfigurasi_prodi` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `visi_prodi` text NOT NULL,
  `misi_prodi` text NOT NULL,
  `sk_akreditasi` varchar(100) NOT NULL,
  `file_sertifikat_pdf` varchar(255) DEFAULT 'sertifikat-akreditasi.pdf'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tabel_prestasi`
--

CREATE TABLE `tabel_prestasi` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) NOT NULL,
  `judul_prestasi` varchar(200) NOT NULL,
  `kategori` enum('himpunan','alumni') NOT NULL,
  `tahun` int(11) NOT NULL,
  `deskripsi` text NOT NULL,
  `gambar_prestasi` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tabel_survei_hrd`
--

CREATE TABLE `tabel_survei_hrd` (
  `id` int(11) NOT NULL,
  `nama_penilai` varchar(150) NOT NULL,
  `jabatan_penilai` varchar(100) NOT NULL,
  `nama_perusahaan` varchar(150) NOT NULL,
  `alamat_perusahaan` text NOT NULL,
  `nama_alumni` varchar(150) NOT NULL,
  `jenjang_pendidikan` varchar(50) NOT NULL,
  `program_studi` varchar(100) NOT NULL,
  `bidang_pekerjaan` varchar(100) NOT NULL,
  `jabatan_alumni` varchar(100) NOT NULL,
  `masa_kerja_alumni` varchar(50) NOT NULL,
  `int_disiplin` tinyint(4) NOT NULL,
  `int_transparan` tinyint(4) NOT NULL,
  `int_dorongan` tinyint(4) NOT NULL,
  `int_komitmen` tinyint(4) NOT NULL,
  `int_kebenaran` tinyint(4) NOT NULL,
  `int_sopan` tinyint(4) NOT NULL,
  `int_adaptif` tinyint(4) NOT NULL,
  `int_masukan` text DEFAULT NULL,
  `prof_penguasaan` tinyint(4) NOT NULL,
  `prof_efisien` tinyint(4) NOT NULL,
  `prof_ide` tinyint(4) NOT NULL,
  `prof_analitis` tinyint(4) NOT NULL,
  `prof_proaktif` tinyint(4) NOT NULL,
  `prof_masukan` text DEFAULT NULL,
  `bhs_tulis` tinyint(4) NOT NULL,
  `bhs_bicara` tinyint(4) NOT NULL,
  `bhs_serap_info` tinyint(4) NOT NULL,
  `bhs_masukan` text DEFAULT NULL,
  `tek_wawasan` tinyint(4) NOT NULL,
  `tek_belajar` tinyint(4) NOT NULL,
  `tek_mahir` tinyint(4) NOT NULL,
  `tek_masukan` text DEFAULT NULL,
  `kom_tekanan` tinyint(4) NOT NULL,
  `kom_tangkas` tinyint(4) NOT NULL,
  `kom_reseptif` tinyint(4) NOT NULL,
  `kom_efektif` tinyint(4) NOT NULL,
  `kom_rapi` tinyint(4) NOT NULL,
  `kom_masukan` text DEFAULT NULL,
  `tim_inisiatif` tinyint(4) NOT NULL,
  `tim_organisir` tinyint(4) NOT NULL,
  `tim_solusi` tinyint(4) NOT NULL,
  `tim_masukan` text DEFAULT NULL,
  `peng_eksplorasi` tinyint(4) NOT NULL,
  `peng_upskilling` tinyint(4) NOT NULL,
  `peng_masukan` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tabel_survei_hrd`
--

INSERT INTO `tabel_survei_hrd` (`id`, `nama_penilai`, `jabatan_penilai`, `nama_perusahaan`, `alamat_perusahaan`, `nama_alumni`, `jenjang_pendidikan`, `program_studi`, `bidang_pekerjaan`, `jabatan_alumni`, `masa_kerja_alumni`, `int_disiplin`, `int_transparan`, `int_dorongan`, `int_komitmen`, `int_kebenaran`, `int_sopan`, `int_adaptif`, `int_masukan`, `prof_penguasaan`, `prof_efisien`, `prof_ide`, `prof_analitis`, `prof_proaktif`, `prof_masukan`, `bhs_tulis`, `bhs_bicara`, `bhs_serap_info`, `bhs_masukan`, `tek_wawasan`, `tek_belajar`, `tek_mahir`, `tek_masukan`, `kom_tekanan`, `kom_tangkas`, `kom_reseptif`, `kom_efektif`, `kom_rapi`, `kom_masukan`, `tim_inisiatif`, `tim_organisir`, `tim_solusi`, `tim_masukan`, `peng_eksplorasi`, `peng_upskilling`, `peng_masukan`, `created_at`) VALUES
(1, 'Bapak Budi Santoso', 'HR Manager', 'Techarea Digital Agency', 'Jl. Imam Bonjol, Semarang', 'Bryan Baskoro', 'S1', 'Sistem Informasi', 'Software Engineering', 'Junior Web Developer', '1 Tahun 2 Bulan', 4, 4, 3, 4, 4, 4, 4, 'Bryan memiliki etika kerja yang sangat baik dan cepat beradaptasi.', 4, 3, 4, 4, 3, NULL, 3, 3, 4, NULL, 4, 4, 4, NULL, 3, 4, 4, 4, 4, NULL, 3, 4, 4, NULL, 4, 4, NULL, '2026-07-07 23:19:16'),
(2, 'Bryan', 'CEO', 'UDINUS', 'Imam Bonjol', 'Ucok', 'S1', 'Sistem Informasi', 'Bidang IT', 'Manajer', '2 Tahun', 4, 4, 3, 3, 3, 3, 3, '', 3, 3, 3, 3, 3, '', 3, 3, 3, '', 3, 3, 3, '', 3, 3, 3, 3, 3, 'Belajar lagi mase', 3, 3, 3, '', 3, 3, '', '2026-07-07 23:20:49');

-- --------------------------------------------------------

--
-- Table structure for table `tabel_tracer_study`
--

CREATE TABLE `tabel_tracer_study` (
  `id` int(11) NOT NULL,
  `alumni_id` int(11) NOT NULL,
  `status_aktivitas` varchar(50) NOT NULL,
  `bekerja_waktu_tunggu` varchar(50) DEFAULT NULL,
  `bekerja_tingkat_jabatan` varchar(100) DEFAULT NULL,
  `bekerja_klasifikasi_institusi` varchar(100) DEFAULT NULL,
  `bekerja_skala_operasional` varchar(50) DEFAULT NULL,
  `bekerja_provinsi` varchar(100) DEFAULT NULL,
  `bekerja_pendapatan` varchar(50) DEFAULT NULL,
  `bekerja_keselarasan_ilmu` varchar(50) DEFAULT NULL,
  `wirausaha_waktu_tunggu` varchar(50) DEFAULT NULL,
  `wirausaha_posisi` varchar(100) DEFAULT NULL,
  `wirausaha_legalitas` varchar(100) DEFAULT NULL,
  `wirausaha_provinsi` varchar(100) DEFAULT NULL,
  `wirausaha_keuntungan` varchar(50) DEFAULT NULL,
  `wirausaha_keselarasan` varchar(50) DEFAULT NULL,
  `studi_nama_pt` varchar(150) DEFAULT NULL,
  `studi_program` varchar(100) DEFAULT NULL,
  `studi_akreditasi` varchar(10) DEFAULT NULL,
  `studi_sumber_biaya` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `tabel_users`
--

CREATE TABLE `tabel_users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','alumni') NOT NULL DEFAULT 'alumni',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `tabel_users`
--

INSERT INTO `tabel_users` (`id`, `username`, `email`, `phone`, `password`, `role`, `created_at`) VALUES
(3, 'A12.2019.00001', 'andika@alumni.dinus.ac.id', '08111111111', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'alumni', '2026-07-08 00:27:14'),
(4, 'A12.2020.00002', 'siska@alumni.dinus.ac.id', '08222222222', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'alumni', '2026-07-08 00:27:14'),
(5, 'A12.2021.00003', 'reza@alumni.dinus.ac.id', '08333333333', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'alumni', '2026-07-08 00:27:14'),
(6, 'kaprodi', 'kaprodi@si.dinus.ac.id', '08111222333', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '2026-07-08 00:46:29'),
(7, 'sekreprodi', 'sekretariat@si.dinus.ac.id', '08555666777', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', '2026-07-08 00:46:29');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `tabel_alumni_pengalaman`
--
ALTER TABLE `tabel_alumni_pengalaman`
  ADD PRIMARY KEY (`id`),
  ADD KEY `alumni_id` (`alumni_id`);

--
-- Indexes for table `tabel_alumni_profil`
--
ALTER TABLE `tabel_alumni_profil`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nim` (`nim`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `tabel_alumni_sertifikasi`
--
ALTER TABLE `tabel_alumni_sertifikasi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `alumni_id` (`alumni_id`);

--
-- Indexes for table `tabel_dosen`
--
ALTER TABLE `tabel_dosen`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `tabel_konfigurasi_prodi`
--
ALTER TABLE `tabel_konfigurasi_prodi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `tabel_prestasi`
--
ALTER TABLE `tabel_prestasi`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Indexes for table `tabel_survei_hrd`
--
ALTER TABLE `tabel_survei_hrd`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `tabel_tracer_study`
--
ALTER TABLE `tabel_tracer_study`
  ADD PRIMARY KEY (`id`),
  ADD KEY `alumni_id` (`alumni_id`);

--
-- Indexes for table `tabel_users`
--
ALTER TABLE `tabel_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `tabel_alumni_pengalaman`
--
ALTER TABLE `tabel_alumni_pengalaman`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tabel_alumni_profil`
--
ALTER TABLE `tabel_alumni_profil`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `tabel_alumni_sertifikasi`
--
ALTER TABLE `tabel_alumni_sertifikasi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tabel_dosen`
--
ALTER TABLE `tabel_dosen`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `tabel_konfigurasi_prodi`
--
ALTER TABLE `tabel_konfigurasi_prodi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tabel_prestasi`
--
ALTER TABLE `tabel_prestasi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `tabel_survei_hrd`
--
ALTER TABLE `tabel_survei_hrd`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `tabel_tracer_study`
--
ALTER TABLE `tabel_tracer_study`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `tabel_users`
--
ALTER TABLE `tabel_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `tabel_alumni_pengalaman`
--
ALTER TABLE `tabel_alumni_pengalaman`
  ADD CONSTRAINT `tabel_alumni_pengalaman_ibfk_1` FOREIGN KEY (`alumni_id`) REFERENCES `tabel_alumni_profil` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tabel_alumni_profil`
--
ALTER TABLE `tabel_alumni_profil`
  ADD CONSTRAINT `tabel_alumni_profil_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `tabel_users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tabel_alumni_sertifikasi`
--
ALTER TABLE `tabel_alumni_sertifikasi`
  ADD CONSTRAINT `tabel_alumni_sertifikasi_ibfk_1` FOREIGN KEY (`alumni_id`) REFERENCES `tabel_alumni_profil` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tabel_dosen`
--
ALTER TABLE `tabel_dosen`
  ADD CONSTRAINT `tabel_dosen_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `tabel_users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tabel_konfigurasi_prodi`
--
ALTER TABLE `tabel_konfigurasi_prodi`
  ADD CONSTRAINT `tabel_konfigurasi_prodi_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `tabel_users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tabel_prestasi`
--
ALTER TABLE `tabel_prestasi`
  ADD CONSTRAINT `tabel_prestasi_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `tabel_users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tabel_tracer_study`
--
ALTER TABLE `tabel_tracer_study`
  ADD CONSTRAINT `tabel_tracer_study_ibfk_1` FOREIGN KEY (`alumni_id`) REFERENCES `tabel_alumni_profil` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
