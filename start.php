<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
chdir(__DIR__);
echo "Neurora berjalan di http://127.0.0.1:8000\n";
passthru(escapeshellarg(PHP_BINARY) . ' -S 127.0.0.1:8000 ' . escapeshellarg(__DIR__ . '/router.php'), $status);
exit($status);
