<?php
declare(strict_types=1);
require __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json; charset=utf-8');

function out(bool $ok, string $msg, int $code = 200): void {
    http_response_code($code);
    echo json_encode(['ok' => $ok, 'message' => $msg]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') out(false, 'Metode tidak diizinkan.', 405);
if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) out(false, 'Sesi tidak valid, muat ulang halaman.', 419);
if (!empty($_POST['website'])) out(true, 'Terima kasih!');                       // honeypot spam
if (time() - ($_SESSION['last_msg'] ?? 0) < 20) out(false, 'Tunggu sebentar sebelum mengirim lagi.', 429);

$name = trim((string)($_POST['name'] ?? ''));
$email = trim((string)($_POST['email'] ?? ''));
$msg = trim((string)($_POST['message'] ?? ''));

if ($name === '' || mb_strlen($name) > 100) out(false, 'Nama wajib diisi.', 422);
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) out(false, 'Email tidak valid.', 422);
if (mb_strlen($msg) < 5 || mb_strlen($msg) > 2000) out(false, 'Pesan minimal 5 karakter.', 422);

$db = db();
if (!$db) out(false, 'Database belum terhubung. Import database/neurora.sql dan cek config/database.php.', 500);

try {
    $st = $db->prepare('INSERT INTO contact_messages (name, email, message, ip_address) VALUES (?,?,?,?)');
    $st->execute([$name, $email, $msg, $_SERVER['REMOTE_ADDR'] ?? null]);
    $_SESSION['last_msg'] = time();
    out(true, 'Pesan terkirim. Terima kasih! 🌱');
} catch (Throwable $e) {
    out(false, 'Gagal menyimpan pesan.', 500);
}
