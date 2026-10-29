<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli-server') { http_response_code(404); exit; }
$path = rawurldecode((string)parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));
if (str_starts_with($path, '/assets/')) {
    $root = realpath(__DIR__ . '/assets');
    $file = realpath(__DIR__ . $path);
    $types = ['css' => 'text/css', 'js' => 'application/javascript', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'svg' => 'image/svg+xml', 'webp' => 'image/webp'];
    $extension = strtolower(pathinfo($file ?: '', PATHINFO_EXTENSION));
    if ($root && $file && str_starts_with($file, $root . DIRECTORY_SEPARATOR) && is_file($file) && isset($types[$extension])) {
        header('Content-Type: ' . $types[$extension]);
        header('X-Content-Type-Options: nosniff');
        readfile($file);
        exit;
    }
    http_response_code(404);
    exit;
}
require __DIR__ . '/api/index.php';
