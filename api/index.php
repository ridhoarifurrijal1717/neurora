<?php
declare(strict_types=1);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
if (getenv('VERCEL')) {
    ini_set('session.save_path', '/tmp');
}
$secure = getenv('VERCEL') || (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
ini_set('session.cookie_secure', $secure ? '1' : '0');
header('Cache-Control: private, no-store, max-age=0');
header('X-Content-Type-Options: nosniff');
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if ($path === '/api/health.php') {
    require __DIR__ . '/../app/config/database.php';
    header('Content-Type: application/json; charset=utf-8');
    $database = db();
    $ready = false;
    if ($database) { try { $database->query('SELECT 1 FROM contact_messages LIMIT 1'); $ready = true; } catch (Throwable $e) {} }
    echo json_encode(['status' => 'ok', 'database' => $ready ? 'ready' : 'unavailable']);
    exit;
}
$routes = [
    '/' => 'index.php', '/index.php' => 'index.php',
    '/problem.php' => 'problem.php', '/solution.php' => 'solution.php',
    '/impact.php' => 'impact.php', '/api/search.php' => 'api/search.php',
    '/api/chat.php' => 'api/chat.php', '/api/solution.php' => 'api/solution.php',
    '/api/contact.php' => 'api/contact.php',
];
if (!is_string($path) || !isset($routes[$path])) { http_response_code(404); echo 'Halaman tidak ditemukan.'; exit; }
require __DIR__ . '/../app/' . $routes[$path];
