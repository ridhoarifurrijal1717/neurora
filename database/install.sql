-- NEURORA: DATABASE LENGKAP
-- Alur: Problem -> Chat AI -> Rencana Solusi -> Dugaan Dampak.
-- Target: MySQL 8.0.16+ atau MariaDB 10.4+, engine InnoDB, UTF-8 penuh.
-- Import melalui phpMyAdmin > Import, atau mysql -u root -p < neurora-complete.sql
-- Membuat database neurora jika belum ada. Tidak ada DROP/TRUNCATE.
-- Import ulang tidak menimpa isi kategori/problem yang sudah diedit.
-- IF NOT EXISTS bukan migrasi struktur tabel lama yang namanya sama.
--
-- PENTING UNTUK ZIP WEBSITE VERSI CHAT:
-- Kode PHP saat ini membaca kategori dari includes/ecosystem.php dan menyimpan
-- chat/rencana dalam sesi PHP. Hanya contact_messages yang sudah dipakai PDO.
-- Tabel eco_* di bawah menyediakan skema lengkap untuk integrasi penyimpanan
-- database. Import SQL saja belum mengubah mekanisme sesi menjadi database.
-- Kunci API tetap di environment/config server, bukan dalam database ini.
-- Waktu database disimpan dalam UTC; tampilkan Asia/Jakarta pada antarmuka.
-- Validasi: lolos parser dialek MySQL dan pencocokan data katalog PHP.
-- Belum diuji impor pada server MySQL/MariaDB nyata.

SET @NEURORA_OLD_SQL_MODE = @@SESSION.sql_mode;
SET @NEURORA_OLD_TIME_ZONE = @@SESSION.time_zone;
SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ENGINE_SUBSTITUTION,NO_BACKSLASH_ESCAPES';
SET SESSION time_zone = '+00:00';
SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE DATABASE IF NOT EXISTS neurora
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE neurora;

-- 1. Kategori ekosistem.
CREATE TABLE IF NOT EXISTS eco_categories (
  slug VARCHAR(40) NOT NULL,
  name VARCHAR(80) NOT NULL,
  icon VARCHAR(30) NOT NULL DEFAULT 'leaf',
  sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (slug),
  CONSTRAINT chk_eco_category_active CHECK (is_active IN (0,1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Problem dan indikator bawaan, sama dengan katalog PHP terbaru.
CREATE TABLE IF NOT EXISTS eco_problems (
  slug VARCHAR(40) NOT NULL,
  category_slug VARCHAR(40) NOT NULL,
  title VARCHAR(160) NOT NULL,
  description TEXT NOT NULL,
  default_complaint TEXT NOT NULL,
  icon VARCHAR(30) NOT NULL DEFAULT 'leaf',
  baseline_label VARCHAR(200) NOT NULL,
  unit VARCHAR(20) NOT NULL,
  default_baseline DECIMAL(14,2) NOT NULL,
  target_label VARCHAR(200) NOT NULL,
  default_target_percent DECIMAL(5,2) NOT NULL,
  is_daily TINYINT(1) NOT NULL DEFAULT 1,
  impact_label VARCHAR(200) NOT NULL,
  measurement_guidance TEXT NOT NULL,
  suggested_action TEXT NOT NULL,
  sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (slug),
  KEY idx_eco_problem_category (category_slug, sort_order),
  CONSTRAINT fk_eco_problem_category FOREIGN KEY (category_slug)
    REFERENCES eco_categories(slug) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT chk_eco_problem_baseline CHECK (default_baseline > 0 AND default_baseline <= 1000000),
  CONSTRAINT chk_eco_problem_target CHECK (default_target_percent BETWEEN 0 AND 100),
  CONSTRAINT chk_eco_problem_daily CHECK (is_daily IN (0,1)),
  CONSTRAINT chk_eco_problem_active CHECK (is_active IN (0,1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Saran pertanyaan pada tampilan awal chat.
CREATE TABLE IF NOT EXISTS eco_question_suggestions (
  id SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  problem_slug VARCHAR(40) NOT NULL,
  question VARCHAR(300) NOT NULL,
  sort_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (id),
  UNIQUE KEY uq_eco_suggestion_order (problem_slug, sort_order),
  CONSTRAINT fk_eco_suggestion_problem FOREIGN KEY (problem_slug)
    REFERENCES eco_problems(slug) ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Kepemilikan data pengunjung anonim.
-- Gunakan hash SHA-256 ID sesi dari server; jangan simpan cookie/ID sesi mentah.
-- Backend wajib membatasi query dengan session_hash pengunjung yang sedang aktif.
CREATE TABLE IF NOT EXISTS eco_visitor_sessions (
  session_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  last_seen_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  expires_at DATETIME NOT NULL,
  PRIMARY KEY (session_hash),
  KEY idx_eco_visitor_expiry (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Percakapan per kategori. ID 24 karakter heksadesimal cocok dengan PHP.
-- provider_history memuat input/output lengkap penyedia untuk konteks lanjutan.
-- Kolom ini hanya untuk server; jangan tampilkan atau kirim ke browser.
CREATE TABLE IF NOT EXISTS eco_conversations (
  id CHAR(24) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  visitor_session_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  problem_slug VARCHAR(40) NOT NULL,
  location_context VARCHAR(300) NOT NULL DEFAULT '',
  turn_count TINYINT UNSIGNED NOT NULL DEFAULT 0,
  provider_history JSON NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  expires_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_eco_conversation_visitor (visitor_session_hash, updated_at),
  KEY idx_eco_conversation_problem (problem_slug),
  KEY idx_eco_conversation_expiry (expires_at),
  CONSTRAINT fk_eco_conversation_visitor FOREIGN KEY (visitor_session_hash)
    REFERENCES eco_visitor_sessions(session_hash) ON DELETE CASCADE,
  CONSTRAINT fk_eco_conversation_problem FOREIGN KEY (problem_slug)
    REFERENCES eco_problems(slug) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT chk_eco_conversation_turn CHECK (turn_count BETWEEN 0 AND 12),
  CONSTRAINT chk_eco_conversation_history CHECK (provider_history IS NULL OR JSON_TYPE(provider_history) = 'ARRAY')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Pesan yang terlihat di chat. Satu tanya jawab = user + assistant.
CREATE TABLE IF NOT EXISTS eco_messages (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  conversation_id CHAR(24) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  position SMALLINT UNSIGNED NOT NULL,
  role ENUM('user','assistant') NOT NULL,
  content TEXT NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_eco_message_position (conversation_id, position),
  CONSTRAINT fk_eco_message_conversation FOREIGN KEY (conversation_id)
    REFERENCES eco_conversations(id) ON DELETE CASCADE,
  CONSTRAINT chk_eco_message_position CHECK (position BETWEEN 1 AND 24),
  CONSTRAINT chk_eco_message_content CHECK (
    (role = 'user' AND CHAR_LENGTH(content) BETWEEN 1 AND 2000) OR
    (role = 'assistant' AND CHAR_LENGTH(content) BETWEEN 1 AND 8000)
  )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Snapshot rencana solusi dan input awalnya.
-- Struktur JSON mengikuti keluaran includes/ai-client.php:
-- possible_causes: ["penyebab", ...]
-- steps: [{"title":"...","action":"...","timing":"..."}, ...]
-- measurement_plan: ["cara ukur", ...]
-- Backend wajib memastikan percakapan dan solusi dimiliki sesi/kategori yang sama.
CREATE TABLE IF NOT EXISTS eco_solutions (
  id CHAR(24) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  visitor_session_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  problem_slug VARCHAR(40) NOT NULL,
  conversation_id CHAR(24) CHARACTER SET ascii COLLATE ascii_bin NULL,
  conversation_turn TINYINT UNSIGNED NULL,
  mode ENUM('ai','example') NOT NULL DEFAULT 'ai',
  complaint TEXT NOT NULL,
  location_context VARCHAR(300) NOT NULL DEFAULT '',
  initial_baseline DECIMAL(14,2) NOT NULL,
  initial_target_percent DECIMAL(5,2) NOT NULL,
  initial_days SMALLINT UNSIGNED NOT NULL,
  summary TEXT NOT NULL,
  possible_causes JSON NOT NULL,
  steps JSON NOT NULL,
  impact_hypothesis TEXT NOT NULL,
  measurement_plan JSON NOT NULL,
  caution TEXT NOT NULL,
  model VARCHAR(128) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  expires_at DATETIME NOT NULL,
  PRIMARY KEY (id),
  KEY idx_eco_solution_visitor (visitor_session_hash, created_at),
  KEY idx_eco_solution_problem (problem_slug),
  KEY idx_eco_solution_conversation (conversation_id, created_at),
  KEY idx_eco_solution_expiry (expires_at),
  CONSTRAINT fk_eco_solution_visitor FOREIGN KEY (visitor_session_hash)
    REFERENCES eco_visitor_sessions(session_hash) ON DELETE CASCADE,
  CONSTRAINT fk_eco_solution_problem FOREIGN KEY (problem_slug)
    REFERENCES eco_problems(slug) ON UPDATE CASCADE ON DELETE RESTRICT,
  CONSTRAINT fk_eco_solution_conversation FOREIGN KEY (conversation_id)
    REFERENCES eco_conversations(id) ON DELETE SET NULL,
  CONSTRAINT chk_eco_solution_turn CHECK (conversation_turn IS NULL OR conversation_turn BETWEEN 0 AND 12),
  CONSTRAINT chk_eco_solution_baseline CHECK (initial_baseline > 0 AND initial_baseline <= 1000000),
  CONSTRAINT chk_eco_solution_target CHECK (initial_target_percent BETWEEN 0 AND 100),
  CONSTRAINT chk_eco_solution_days CHECK (initial_days BETWEEN 1 AND 365),
  CONSTRAINT chk_eco_solution_causes CHECK (JSON_TYPE(possible_causes) = 'ARRAY'),
  CONSTRAINT chk_eco_solution_steps CHECK (JSON_TYPE(steps) = 'ARRAY'),
  CONSTRAINT chk_eco_solution_measurements CHECK (JSON_TYPE(measurement_plan) = 'ARRAY')
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Skenario dampak dari rencana. Salin unit/indikator ketika membuat skenario
-- agar perubahan katalog di masa depan tidak mengubah arti hasil yang tersimpan.
-- Angka adalah asumsi, bukan pengukuran atau prediksi tervalidasi.
CREATE TABLE IF NOT EXISTS eco_impact_scenarios (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  solution_id CHAR(24) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  name VARCHAR(100) NOT NULL DEFAULT 'Skenario awal',
  baseline DECIMAL(14,2) NOT NULL,
  target_percent DECIMAL(5,2) NOT NULL,
  days SMALLINT UNSIGNED NOT NULL,
  is_daily TINYINT(1) NOT NULL,
  unit VARCHAR(20) NOT NULL,
  baseline_label VARCHAR(200) NOT NULL,
  impact_label VARCHAR(200) NOT NULL,
  measurement_guidance TEXT NOT NULL,
  assumption_source ENUM('example','visitor_input') NOT NULL DEFAULT 'visitor_input',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_eco_scenario_solution (solution_id, created_at),
  CONSTRAINT fk_eco_scenario_solution FOREIGN KEY (solution_id)
    REFERENCES eco_solutions(id) ON DELETE CASCADE,
  CONSTRAINT chk_eco_scenario_baseline CHECK (baseline > 0 AND baseline <= 1000000),
  CONSTRAINT chk_eco_scenario_target CHECK (target_percent BETWEEN 0 AND 100),
  CONSTRAINT chk_eco_scenario_days CHECK (days BETWEEN 1 AND 365),
  CONSTRAINT chk_eco_scenario_daily CHECK (is_daily IN (0,1))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. Hasil pengukuran lapangan, terpisah dari asumsi simulasi.
-- Biarkan kosong sampai pengukuran benar-benar dilakukan.
CREATE TABLE IF NOT EXISTS eco_impact_observations (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  scenario_id BIGINT UNSIGNED NOT NULL,
  observed_at DATETIME NOT NULL,
  before_value DECIMAL(16,4) NOT NULL,
  after_value DECIMAL(16,4) NOT NULL,
  unit VARCHAR(20) NOT NULL,
  conditions TEXT NOT NULL,
  notes TEXT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_eco_observation_scenario (scenario_id, observed_at),
  CONSTRAINT fk_eco_observation_scenario FOREIGN KEY (scenario_id)
    REFERENCES eco_impact_scenarios(id) ON DELETE CASCADE,
  CONSTRAINT chk_eco_observation_before CHECK (before_value >= 0),
  CONSTRAINT chk_eco_observation_after CHECK (after_value >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. Log permintaan untuk kuota/audit. Jangan simpan kunci atau error mentah.
CREATE TABLE IF NOT EXISTS eco_ai_request_log (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  visitor_session_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
  conversation_id CHAR(24) CHARACTER SET ascii COLLATE ascii_bin NULL,
  solution_id CHAR(24) CHARACTER SET ascii COLLATE ascii_bin NULL,
  kind ENUM('chat','solution','example') NOT NULL,
  status ENUM('success','failed','limited') NOT NULL,
  model VARCHAR(128) NULL,
  input_tokens INT UNSIGNED NULL,
  output_tokens INT UNSIGNED NULL,
  error_code VARCHAR(64) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_eco_request_visitor_time (visitor_session_hash, created_at),
  KEY idx_eco_request_conversation (conversation_id),
  KEY idx_eco_request_solution (solution_id),
  CONSTRAINT fk_eco_request_visitor FOREIGN KEY (visitor_session_hash)
    REFERENCES eco_visitor_sessions(session_hash) ON DELETE CASCADE,
  CONSTRAINT fk_eco_request_conversation FOREIGN KEY (conversation_id)
    REFERENCES eco_conversations(id) ON DELETE SET NULL,
  CONSTRAINT fk_eco_request_solution FOREIGN KEY (solution_id)
    REFERENCES eco_solutions(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. Kontak: nama tabel/kolom dipertahankan sesuai api/contact.php.
CREATE TABLE IF NOT EXISTS contact_messages (
  id INT NOT NULL AUTO_INCREMENT,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL,
  message TEXT NOT NULL,
  ip_address VARCHAR(45) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- View kalkulator: sama dengan estimate_impact() pada includes/ecosystem.php.
-- Indikator harian: baseline * days * target_percent / 100.
-- Luas ruang hijau: baseline * target_percent / 100 (hari bukan pengali luas).
-- Variasi target 80% dan 120% relatif, dibatasi 0..100; bukan interval statistik.
CREATE OR REPLACE SQL SECURITY INVOKER VIEW v_eco_impact_estimates AS
SELECT
  s.id AS scenario_id,
  s.solution_id,
  sol.problem_slug,
  s.name AS scenario_name,
  s.unit,
  s.baseline,
  s.target_percent,
  s.days,
  s.is_daily,
  s.impact_label,
  s.assumption_source,
  ROUND(s.baseline * CASE WHEN s.is_daily = 1 THEN s.days ELSE 1 END, 2) AS baseline_total,
  ROUND(s.baseline * CASE WHEN s.is_daily = 1 THEN s.days ELSE 1 END * s.target_percent / 100, 2) AS projected_benefit,
  ROUND(s.baseline * CASE WHEN s.is_daily = 1 THEN s.days ELSE 1 END * (1 - s.target_percent / 100), 2) AS projected_remaining,
  ROUND(GREATEST(0, s.target_percent * 0.8), 2) AS lower_target_percent,
  ROUND(LEAST(100, s.target_percent * 1.2), 2) AS upper_target_percent,
  ROUND(s.baseline * CASE WHEN s.is_daily = 1 THEN s.days ELSE 1 END * GREATEST(0, s.target_percent * 0.8) / 100, 2) AS lower_scenario,
  ROUND(s.baseline * CASE WHEN s.is_daily = 1 THEN s.days ELSE 1 END * LEAST(100, s.target_percent * 1.2) / 100, 2) AS upper_scenario
FROM eco_impact_scenarios AS s
JOIN eco_solutions AS sol ON sol.id = s.solution_id;

-- DATA AWAL: hanya katalog dan saran pertanyaan.
-- Nilai baseline/target bawaan adalah contoh asumsi, bukan temuan penelitian.
START TRANSACTION;

INSERT INTO eco_categories (slug, name, icon, sort_order, is_active) VALUES
('udara', 'Udara', 'cloud', 1, 1),
('air', 'Air', 'water', 2, 1),
('sampah', 'Sampah', 'recycle', 3, 1),
('tanah', 'Tanah', 'leaf', 4, 1),
('energi', 'Energi', 'bolt', 5, 1),
('ruang-hijau', 'Ruang hijau', 'tree', 6, 1)
ON DUPLICATE KEY UPDATE slug = eco_categories.slug;

INSERT INTO eco_problems (slug, category_slug, title, description, default_complaint, icon, baseline_label, unit, default_baseline, target_label, default_target_percent, is_daily, impact_label, measurement_guidance, suggested_action, sort_order, is_active) VALUES
('udara', 'udara', 'Asap pembakaran sampah mengganggu', 'Udara di sekitar rumah berasap karena sampah dibakar. Ingin mencari cara menguranginya?', 'Sampah masih dibakar di sekitar rumah saya dan asapnya mengganggu warga.', 'cloud', 'Sampah yang biasa dibakar per hari', 'kg', 5, 'Target sampah yang dialihkan dari pembakaran', 60, 1, 'Sampah tidak jadi dibakar', 'Timbang sampah yang sebelumnya dibakar dan catat jumlah yang berhasil dialihkan ke pengelolaan yang aman. Angka ini tidak mengukur konsentrasi polutan udara.', 'Pisahkan sampah dari sumbernya dan sepakati jadwal pengangkutan bersama warga agar tidak perlu membakar sampah.', 1, 1),
('air', 'air', 'Air terbuang dan saluran tercemar', 'Keran bocor, pemakaian berlebihan, atau saluran kotor? Mulai dari kebiasaan dan sumber masalahnya.', 'Pemakaian air di rumah saya boros dan saluran sekitar sering kotor. Saya ingin mulai menghemat air.', 'water', 'Pemakaian air awal per hari', 'liter', 200, 'Target penghematan pemakaian air', 20, 1, 'Pemakaian air yang dihemat', 'Bandingkan meter air pada jam yang sama sebelum dan sesudah tindakan. Penghematan volume tidak membuktikan air layak minum atau bebas pencemar.', 'Periksa kebocoran, catat meter air harian, dan hentikan pembuangan sampah atau bahan kimia ke saluran. Untuk pencemaran, hubungi pengelola air atau dinas terkait.', 2, 1),
('sampah', 'sampah', 'Sampah menumpuk dan masih tercampur', 'Sampah rumah tangga sulit dikelola dan cepat memenuhi tempat pembuangan.', 'Sampah rumah tangga di lingkungan saya masih tercampur dan sering menumpuk.', 'recycle', 'Sampah campuran yang dibuang per hari', 'kg', 10, 'Target sampah yang dikurangi atau dialihkan', 30, 1, 'Sampah campuran yang dialihkan', 'Timbang sampah campuran serta sampah terpilah yang benar-benar diterima pengelola. Pisahkan limbah berbahaya dan elektronik.', 'Kurangi barang sekali pakai, pisahkan organik dan material daur ulang, lalu pastikan jalur pengangkutannya tersedia.', 3, 1),
('tanah', 'tanah', 'Sisa organik terbuang, tanah kurang terawat', 'Sisa dapur dan daun hanya dibuang, sementara tanah di pekarangan membutuhkan perawatan.', 'Sisa dapur dan daun di rumah saya langsung dibuang. Saya ingin mengolahnya untuk merawat tanah.', 'leaf', 'Sisa organik yang dibuang per hari', 'kg', 3, 'Target organik yang diolah dengan aman', 50, 1, 'Bahan organik masuk pengolahan', 'Timbang bahan organik yang masuk pengolahan dan pantau bau, kelembapan, serta kematangan kompos. Berat bahan masuk bukan berat kompos jadi atau ukuran kesuburan tanah.', 'Mulai pengomposan skala kecil dengan bahan yang sesuai, kelola kelembapan dan aerasi, lalu gunakan hanya kompos matang.', 4, 1),
('energi', 'energi', 'Listrik boros karena kebiasaan harian', 'Lampu, pendingin, atau perangkat terus menyala. Temukan langkah efisiensi yang bisa dimulai.', 'Listrik di rumah saya boros karena lampu dan perangkat sering menyala saat tidak digunakan.', 'bolt', 'Pemakaian listrik awal per hari', 'kWh', 15, 'Target penghematan listrik', 15, 1, 'Energi listrik yang dihemat', 'Bandingkan kWh meter pada periode dengan aktivitas dan cuaca serupa. Tidak ada konversi CO₂ tanpa faktor emisi yang sesuai lokasi dan periode.', 'Catat beban utama, atur jadwal pemakaian, matikan perangkat yang tidak digunakan, dan evaluasi konsumsi listrik tiap minggu.', 5, 1),
('ruang-hijau', 'ruang-hijau', 'Pekarangan minim tanaman dan habitat', 'Ada lahan yang belum ditanami dan sedikit tempat bagi penyerbuk atau satwa kecil.', 'Pekarangan di lingkungan saya minim tanaman. Ada lahan yang ingin kami jadikan ruang hijau.', 'tree', 'Luas lahan yang tersedia', 'm²', 50, 'Target bagian lahan yang dihijaukan', 40, 0, 'Lahan yang direncanakan dihijaukan', 'Ukur luas yang benar-benar ditanami, pantau tanaman yang bertahan dan kebutuhan perawatannya. Luas tanam tidak otomatis menunjukkan pemulihan biodiversitas.', 'Petakan lahan, pilih tanaman lokal yang sesuai kondisi setempat, pastikan izin penggunaan lahan, dan susun jadwal perawatan.', 6, 1)
ON DUPLICATE KEY UPDATE slug = eco_problems.slug;

INSERT INTO eco_question_suggestions (id, problem_slug, question, sort_order) VALUES
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
(18, 'ruang-hijau', 'Bagaimana cara mengukur apakah solusinya berhasil?', 3)
ON DUPLICATE KEY UPDATE id = eco_question_suggestions.id;

COMMIT;

-- PEMERIKSAAN SETELAH IMPORT BARU: 6 kategori, 6 problem, 18 saran pertanyaan.
SELECT 'eco_categories' AS data_awal, COUNT(*) AS jumlah FROM eco_categories
UNION ALL SELECT 'eco_problems', COUNT(*) FROM eco_problems
UNION ALL SELECT 'eco_question_suggestions', COUNT(*) FROM eco_question_suggestions;

-- Contoh query setelah backend mulai menyimpan skenario:
-- SELECT * FROM v_eco_impact_estimates ORDER BY scenario_id DESC;
-- Contoh hitung air: 200 liter/hari x 30 hari x 20% = 1200 liter.
-- Contoh ruang hijau: 50 m2 x 40% = 20 m2, tidak dikali 30 hari.
-- Chat, solusi, skenario, dan pengukuran akan diisi backend saat fitur digunakan.

SET SESSION sql_mode = @NEURORA_OLD_SQL_MODE;
SET SESSION time_zone = @NEURORA_OLD_TIME_ZONE;
