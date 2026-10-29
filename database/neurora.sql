-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 29 Okt 2026 pada 20.31
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `neurora`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `contact_messages`
--

CREATE TABLE `contact_messages` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `message` text NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `eco_ai_request_log`
--

CREATE TABLE `eco_ai_request_log` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `visitor_session_hash` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `conversation_id` char(24) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `solution_id` char(24) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `kind` enum('chat','solution','example') NOT NULL,
  `status` enum('success','failed','limited') NOT NULL,
  `model` varchar(128) DEFAULT NULL,
  `input_tokens` int(10) UNSIGNED DEFAULT NULL,
  `output_tokens` int(10) UNSIGNED DEFAULT NULL,
  `error_code` varchar(64) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `eco_categories`
--

CREATE TABLE `eco_categories` (
  `slug` varchar(40) NOT NULL,
  `name` varchar(80) NOT NULL,
  `icon` varchar(30) NOT NULL DEFAULT 'leaf',
  `sort_order` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ;

--
-- Dumping data untuk tabel `eco_categories`
--

INSERT INTO `eco_categories` (`slug`, `name`, `icon`, `sort_order`, `is_active`) VALUES
('air', 'Air', 'water', 2, 1),
('energi', 'Energi', 'bolt', 5, 1),
('ruang-hijau', 'Ruang hijau', 'tree', 6, 1),
('sampah', 'Sampah', 'recycle', 3, 1),
('tanah', 'Tanah', 'leaf', 4, 1),
('udara', 'Udara', 'cloud', 1, 1);

-- --------------------------------------------------------

--
-- Struktur dari tabel `eco_conversations`
--

CREATE TABLE `eco_conversations` (
  `id` char(24) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `visitor_session_hash` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `problem_slug` varchar(40) NOT NULL,
  `location_context` varchar(300) NOT NULL DEFAULT '',
  `turn_count` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `provider_history` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`provider_history`)),
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `expires_at` datetime NOT NULL
) ;

-- --------------------------------------------------------

--
-- Struktur dari tabel `eco_impact_observations`
--

CREATE TABLE `eco_impact_observations` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `scenario_id` bigint(20) UNSIGNED NOT NULL,
  `observed_at` datetime NOT NULL,
  `before_value` decimal(16,4) NOT NULL,
  `after_value` decimal(16,4) NOT NULL,
  `unit` varchar(20) NOT NULL,
  `conditions` text NOT NULL,
  `notes` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ;

-- --------------------------------------------------------

--
-- Struktur dari tabel `eco_impact_scenarios`
--

CREATE TABLE `eco_impact_scenarios` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `solution_id` char(24) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `name` varchar(100) NOT NULL DEFAULT 'Skenario awal',
  `baseline` decimal(14,2) NOT NULL,
  `target_percent` decimal(5,2) NOT NULL,
  `days` smallint(5) UNSIGNED NOT NULL,
  `is_daily` tinyint(1) NOT NULL,
  `unit` varchar(20) NOT NULL,
  `baseline_label` varchar(200) NOT NULL,
  `impact_label` varchar(200) NOT NULL,
  `measurement_guidance` text NOT NULL,
  `assumption_source` enum('example','visitor_input') NOT NULL DEFAULT 'visitor_input',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ;

-- --------------------------------------------------------

--
-- Struktur dari tabel `eco_messages`
--

CREATE TABLE `eco_messages` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `conversation_id` char(24) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `position` smallint(5) UNSIGNED NOT NULL,
  `role` enum('user','assistant') NOT NULL,
  `content` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ;

-- --------------------------------------------------------

--
-- Struktur dari tabel `eco_problems`
--

CREATE TABLE `eco_problems` (
  `slug` varchar(40) NOT NULL,
  `category_slug` varchar(40) NOT NULL,
  `title` varchar(160) NOT NULL,
  `description` text NOT NULL,
  `default_complaint` text NOT NULL,
  `icon` varchar(30) NOT NULL DEFAULT 'leaf',
  `baseline_label` varchar(200) NOT NULL,
  `unit` varchar(20) NOT NULL,
  `default_baseline` decimal(14,2) NOT NULL,
  `target_label` varchar(200) NOT NULL,
  `default_target_percent` decimal(5,2) NOT NULL,
  `is_daily` tinyint(1) NOT NULL DEFAULT 1,
  `impact_label` varchar(200) NOT NULL,
  `measurement_guidance` text NOT NULL,
  `suggested_action` text NOT NULL,
  `sort_order` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1
) ;

--
-- Dumping data untuk tabel `eco_problems`
--

INSERT INTO `eco_problems` (`slug`, `category_slug`, `title`, `description`, `default_complaint`, `icon`, `baseline_label`, `unit`, `default_baseline`, `target_label`, `default_target_percent`, `is_daily`, `impact_label`, `measurement_guidance`, `suggested_action`, `sort_order`, `is_active`) VALUES
('air', 'air', 'Air terbuang dan saluran tercemar', 'Keran bocor, pemakaian berlebihan, atau saluran kotor? Mulai dari kebiasaan dan sumber masalahnya.', 'Pemakaian air di rumah saya boros dan saluran sekitar sering kotor. Saya ingin mulai menghemat air.', 'water', 'Pemakaian air awal per hari', 'liter', 200.00, 'Target penghematan pemakaian air', 20.00, 1, 'Pemakaian air yang dihemat', 'Bandingkan meter air pada jam yang sama sebelum dan sesudah tindakan. Penghematan volume tidak membuktikan air layak minum atau bebas pencemar.', 'Periksa kebocoran, catat meter air harian, dan hentikan pembuangan sampah atau bahan kimia ke saluran. Untuk pencemaran, hubungi pengelola air atau dinas terkait.', 2, 1),
('energi', 'energi', 'Listrik boros karena kebiasaan harian', 'Lampu, pendingin, atau perangkat terus menyala. Temukan langkah efisiensi yang bisa dimulai.', 'Listrik di rumah saya boros karena lampu dan perangkat sering menyala saat tidak digunakan.', 'bolt', 'Pemakaian listrik awal per hari', 'kWh', 15.00, 'Target penghematan listrik', 15.00, 1, 'Energi listrik yang dihemat', 'Bandingkan kWh meter pada periode dengan aktivitas dan cuaca serupa. Tidak ada konversi CO₂ tanpa faktor emisi yang sesuai lokasi dan periode.', 'Catat beban utama, atur jadwal pemakaian, matikan perangkat yang tidak digunakan, dan evaluasi konsumsi listrik tiap minggu.', 5, 1),
('ruang-hijau', 'ruang-hijau', 'Pekarangan minim tanaman dan habitat', 'Ada lahan yang belum ditanami dan sedikit tempat bagi penyerbuk atau satwa kecil.', 'Pekarangan di lingkungan saya minim tanaman. Ada lahan yang ingin kami jadikan ruang hijau.', 'tree', 'Luas lahan yang tersedia', 'm²', 50.00, 'Target bagian lahan yang dihijaukan', 40.00, 0, 'Lahan yang direncanakan dihijaukan', 'Ukur luas yang benar-benar ditanami, pantau tanaman yang bertahan dan kebutuhan perawatannya. Luas tanam tidak otomatis menunjukkan pemulihan biodiversitas.', 'Petakan lahan, pilih tanaman lokal yang sesuai kondisi setempat, pastikan izin penggunaan lahan, dan susun jadwal perawatan.', 6, 1),
('sampah', 'sampah', 'Sampah menumpuk dan masih tercampur', 'Sampah rumah tangga sulit dikelola dan cepat memenuhi tempat pembuangan.', 'Sampah rumah tangga di lingkungan saya masih tercampur dan sering menumpuk.', 'recycle', 'Sampah campuran yang dibuang per hari', 'kg', 10.00, 'Target sampah yang dikurangi atau dialihkan', 30.00, 1, 'Sampah campuran yang dialihkan', 'Timbang sampah campuran serta sampah terpilah yang benar-benar diterima pengelola. Pisahkan limbah berbahaya dan elektronik.', 'Kurangi barang sekali pakai, pisahkan organik dan material daur ulang, lalu pastikan jalur pengangkutannya tersedia.', 3, 1),
('tanah', 'tanah', 'Sisa organik terbuang, tanah kurang terawat', 'Sisa dapur dan daun hanya dibuang, sementara tanah di pekarangan membutuhkan perawatan.', 'Sisa dapur dan daun di rumah saya langsung dibuang. Saya ingin mengolahnya untuk merawat tanah.', 'leaf', 'Sisa organik yang dibuang per hari', 'kg', 3.00, 'Target organik yang diolah dengan aman', 50.00, 1, 'Bahan organik masuk pengolahan', 'Timbang bahan organik yang masuk pengolahan dan pantau bau, kelembapan, serta kematangan kompos. Berat bahan masuk bukan berat kompos jadi atau ukuran kesuburan tanah.', 'Mulai pengomposan skala kecil dengan bahan yang sesuai, kelola kelembapan dan aerasi, lalu gunakan hanya kompos matang.', 4, 1),
('udara', 'udara', 'Asap pembakaran sampah mengganggu', 'Udara di sekitar rumah berasap karena sampah dibakar. Ingin mencari cara menguranginya?', 'Sampah masih dibakar di sekitar rumah saya dan asapnya mengganggu warga.', 'cloud', 'Sampah yang biasa dibakar per hari', 'kg', 5.00, 'Target sampah yang dialihkan dari pembakaran', 60.00, 1, 'Sampah tidak jadi dibakar', 'Timbang sampah yang sebelumnya dibakar dan catat jumlah yang berhasil dialihkan ke pengelolaan yang aman. Angka ini tidak mengukur konsentrasi polutan udara.', 'Pisahkan sampah dari sumbernya dan sepakati jadwal pengangkutan bersama warga agar tidak perlu membakar sampah.', 1, 1);

-- --------------------------------------------------------

--
-- Struktur dari tabel `eco_question_suggestions`
--

CREATE TABLE `eco_question_suggestions` (
  `id` smallint(5) UNSIGNED NOT NULL,
  `problem_slug` varchar(40) NOT NULL,
  `question` varchar(300) NOT NULL,
  `sort_order` smallint(5) UNSIGNED NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `eco_question_suggestions`
--

INSERT INTO `eco_question_suggestions` (`id`, `problem_slug`, `question`, `sort_order`) VALUES
(1, 'udara', 'Apa penyebab masalah ini yang perlu saya periksa?', 1),
(2, 'udara', 'Tolong beri solusi sederhana yang bisa saya mulai hari ini.', 2),
(3, 'udara', 'Bagaimana cara mengukur apakah solusinya berhasil?', 3),
(4, 'air', 'Apa penyebab masalah ini yang perlu saya periksa?', 1),
(5, 'air', 'Tolong beri solusi sederhana yang bisa saya mulai hari ini.', 2),
(6, 'air', 'Bagaimana cara mengukur apakah solusinya berhasil?', 3),
(7, 'sampah', 'Apa penyebab masalah ini yang perlu saya periksa?', 1),
(8, 'sampah', 'Tolong beri solusi sederhana yang bisa saya mulai hari ini.', 2),
(9, 'sampah', 'Bagaimana cara mengukur apakah solusinya berhasil?', 3),
(10, 'tanah', 'Apa penyebab masalah ini yang perlu saya periksa?', 1),
(11, 'tanah', 'Tolong beri solusi sederhana yang bisa saya mulai hari ini.', 2),
(12, 'tanah', 'Bagaimana cara mengukur apakah solusinya berhasil?', 3),
(13, 'energi', 'Apa penyebab masalah ini yang perlu saya periksa?', 1),
(14, 'energi', 'Tolong beri solusi sederhana yang bisa saya mulai hari ini.', 2),
(15, 'energi', 'Bagaimana cara mengukur apakah solusinya berhasil?', 3),
(16, 'ruang-hijau', 'Apa penyebab masalah ini yang perlu saya periksa?', 1),
(17, 'ruang-hijau', 'Tolong beri solusi sederhana yang bisa saya mulai hari ini.', 2),
(18, 'ruang-hijau', 'Bagaimana cara mengukur apakah solusinya berhasil?', 3);

-- --------------------------------------------------------

--
-- Struktur dari tabel `eco_solutions`
--

CREATE TABLE `eco_solutions` (
  `id` char(24) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `visitor_session_hash` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `problem_slug` varchar(40) NOT NULL,
  `conversation_id` char(24) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
  `conversation_turn` tinyint(3) UNSIGNED DEFAULT NULL,
  `mode` enum('ai','example') NOT NULL DEFAULT 'ai',
  `complaint` text NOT NULL,
  `location_context` varchar(300) NOT NULL DEFAULT '',
  `initial_baseline` decimal(14,2) NOT NULL,
  `initial_target_percent` decimal(5,2) NOT NULL,
  `initial_days` smallint(5) UNSIGNED NOT NULL,
  `summary` text NOT NULL,
  `possible_causes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`possible_causes`)),
  `steps` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`steps`)),
  `impact_hypothesis` text NOT NULL,
  `measurement_plan` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`measurement_plan`)),
  `caution` text NOT NULL,
  `model` varchar(128) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `expires_at` datetime NOT NULL
) ;

-- --------------------------------------------------------

--
-- Struktur dari tabel `eco_visitor_sessions`
--

CREATE TABLE `eco_visitor_sessions` (
  `session_hash` char(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `last_seen_at` datetime NOT NULL DEFAULT current_timestamp(),
  `expires_at` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Stand-in struktur untuk tampilan `v_eco_impact_estimates`
-- (Lihat di bawah untuk tampilan aktual)
--
CREATE TABLE `v_eco_impact_estimates` (
`scenario_id` bigint(20) unsigned
,`solution_id` char(24)
,`problem_slug` varchar(40)
,`scenario_name` varchar(100)
,`unit` varchar(20)
,`baseline` decimal(14,2)
,`target_percent` decimal(5,2)
,`days` smallint(5) unsigned
,`is_daily` tinyint(1)
,`impact_label` varchar(200)
,`assumption_source` enum('example','visitor_input')
,`baseline_total` decimal(19,2)
,`projected_benefit` decimal(23,2)
,`projected_remaining` decimal(24,2)
,`lower_target_percent` decimal(7,2)
,`upper_target_percent` decimal(7,2)
,`lower_scenario` decimal(24,2)
,`upper_scenario` decimal(24,2)
);

-- --------------------------------------------------------

--
-- Struktur untuk view `v_eco_impact_estimates`
--
DROP TABLE IF EXISTS `v_eco_impact_estimates`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY INVOKER VIEW `v_eco_impact_estimates`  AS SELECT `s`.`id` AS `scenario_id`, `s`.`solution_id` AS `solution_id`, `sol`.`problem_slug` AS `problem_slug`, `s`.`name` AS `scenario_name`, `s`.`unit` AS `unit`, `s`.`baseline` AS `baseline`, `s`.`target_percent` AS `target_percent`, `s`.`days` AS `days`, `s`.`is_daily` AS `is_daily`, `s`.`impact_label` AS `impact_label`, `s`.`assumption_source` AS `assumption_source`, round(`s`.`baseline` * case when `s`.`is_daily` = 1 then `s`.`days` else 1 end,2) AS `baseline_total`, round(`s`.`baseline` * case when `s`.`is_daily` = 1 then `s`.`days` else 1 end * `s`.`target_percent` / 100,2) AS `projected_benefit`, round(`s`.`baseline` * case when `s`.`is_daily` = 1 then `s`.`days` else 1 end * (1 - `s`.`target_percent` / 100),2) AS `projected_remaining`, round(greatest(0,`s`.`target_percent` * 0.8),2) AS `lower_target_percent`, round(least(100,`s`.`target_percent` * 1.2),2) AS `upper_target_percent`, round(`s`.`baseline` * case when `s`.`is_daily` = 1 then `s`.`days` else 1 end * greatest(0,`s`.`target_percent` * 0.8) / 100,2) AS `lower_scenario`, round(`s`.`baseline` * case when `s`.`is_daily` = 1 then `s`.`days` else 1 end * least(100,`s`.`target_percent` * 1.2) / 100,2) AS `upper_scenario` FROM (`eco_impact_scenarios` `s` join `eco_solutions` `sol` on(`sol`.`id` = `s`.`solution_id`)) ;

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `contact_messages`
--
ALTER TABLE `contact_messages`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `eco_ai_request_log`
--
ALTER TABLE `eco_ai_request_log`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_eco_request_visitor_time` (`visitor_session_hash`,`created_at`),
  ADD KEY `idx_eco_request_conversation` (`conversation_id`),
  ADD KEY `idx_eco_request_solution` (`solution_id`);

--
-- Indeks untuk tabel `eco_categories`
--
ALTER TABLE `eco_categories`
  ADD PRIMARY KEY (`slug`);

--
-- Indeks untuk tabel `eco_conversations`
--
ALTER TABLE `eco_conversations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_eco_conversation_visitor` (`visitor_session_hash`,`updated_at`),
  ADD KEY `idx_eco_conversation_problem` (`problem_slug`),
  ADD KEY `idx_eco_conversation_expiry` (`expires_at`);

--
-- Indeks untuk tabel `eco_impact_observations`
--
ALTER TABLE `eco_impact_observations`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_eco_observation_scenario` (`scenario_id`,`observed_at`);

--
-- Indeks untuk tabel `eco_impact_scenarios`
--
ALTER TABLE `eco_impact_scenarios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_eco_scenario_solution` (`solution_id`,`created_at`);

--
-- Indeks untuk tabel `eco_messages`
--
ALTER TABLE `eco_messages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_eco_message_position` (`conversation_id`,`position`);

--
-- Indeks untuk tabel `eco_problems`
--
ALTER TABLE `eco_problems`
  ADD PRIMARY KEY (`slug`),
  ADD KEY `idx_eco_problem_category` (`category_slug`,`sort_order`);

--
-- Indeks untuk tabel `eco_question_suggestions`
--
ALTER TABLE `eco_question_suggestions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_eco_suggestion_order` (`problem_slug`,`sort_order`);

--
-- Indeks untuk tabel `eco_solutions`
--
ALTER TABLE `eco_solutions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_eco_solution_visitor` (`visitor_session_hash`,`created_at`),
  ADD KEY `idx_eco_solution_problem` (`problem_slug`),
  ADD KEY `idx_eco_solution_conversation` (`conversation_id`,`created_at`),
  ADD KEY `idx_eco_solution_expiry` (`expires_at`);

--
-- Indeks untuk tabel `eco_visitor_sessions`
--
ALTER TABLE `eco_visitor_sessions`
  ADD PRIMARY KEY (`session_hash`),
  ADD KEY `idx_eco_visitor_expiry` (`expires_at`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `contact_messages`
--
ALTER TABLE `contact_messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `eco_ai_request_log`
--
ALTER TABLE `eco_ai_request_log`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `eco_impact_observations`
--
ALTER TABLE `eco_impact_observations`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `eco_impact_scenarios`
--
ALTER TABLE `eco_impact_scenarios`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `eco_messages`
--
ALTER TABLE `eco_messages`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `eco_question_suggestions`
--
ALTER TABLE `eco_question_suggestions`
  MODIFY `id` smallint(5) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=19;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `eco_ai_request_log`
--
ALTER TABLE `eco_ai_request_log`
  ADD CONSTRAINT `fk_eco_request_conversation` FOREIGN KEY (`conversation_id`) REFERENCES `eco_conversations` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_eco_request_solution` FOREIGN KEY (`solution_id`) REFERENCES `eco_solutions` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_eco_request_visitor` FOREIGN KEY (`visitor_session_hash`) REFERENCES `eco_visitor_sessions` (`session_hash`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `eco_conversations`
--
ALTER TABLE `eco_conversations`
  ADD CONSTRAINT `fk_eco_conversation_problem` FOREIGN KEY (`problem_slug`) REFERENCES `eco_problems` (`slug`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_eco_conversation_visitor` FOREIGN KEY (`visitor_session_hash`) REFERENCES `eco_visitor_sessions` (`session_hash`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `eco_impact_observations`
--
ALTER TABLE `eco_impact_observations`
  ADD CONSTRAINT `fk_eco_observation_scenario` FOREIGN KEY (`scenario_id`) REFERENCES `eco_impact_scenarios` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `eco_impact_scenarios`
--
ALTER TABLE `eco_impact_scenarios`
  ADD CONSTRAINT `fk_eco_scenario_solution` FOREIGN KEY (`solution_id`) REFERENCES `eco_solutions` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `eco_messages`
--
ALTER TABLE `eco_messages`
  ADD CONSTRAINT `fk_eco_message_conversation` FOREIGN KEY (`conversation_id`) REFERENCES `eco_conversations` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `eco_problems`
--
ALTER TABLE `eco_problems`
  ADD CONSTRAINT `fk_eco_problem_category` FOREIGN KEY (`category_slug`) REFERENCES `eco_categories` (`slug`) ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `eco_question_suggestions`
--
ALTER TABLE `eco_question_suggestions`
  ADD CONSTRAINT `fk_eco_suggestion_problem` FOREIGN KEY (`problem_slug`) REFERENCES `eco_problems` (`slug`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Ketidakleluasaan untuk tabel `eco_solutions`
--
ALTER TABLE `eco_solutions`
  ADD CONSTRAINT `fk_eco_solution_conversation` FOREIGN KEY (`conversation_id`) REFERENCES `eco_conversations` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_eco_solution_problem` FOREIGN KEY (`problem_slug`) REFERENCES `eco_problems` (`slug`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_eco_solution_visitor` FOREIGN KEY (`visitor_session_hash`) REFERENCES `eco_visitor_sessions` (`session_hash`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
