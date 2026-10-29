-- Instalasi baru: database hanya diperlukan untuk form kontak.
-- Katalog ekosistem: includes/ecosystem.php. Solusi dan dampak: sesi PHP.
-- Aman dijalankan pada instalasi lama; tidak menghapus tabel atau pesan yang ada.
CREATE DATABASE IF NOT EXISTS neurora CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE neurora;
CREATE TABLE IF NOT EXISTS contact_messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL,
  message TEXT NOT NULL,
  ip_address VARCHAR(45) DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
