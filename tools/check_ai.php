<?php
/** Pemeriksaan untuk operator dari terminal; tidak tersedia sebagai halaman web. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require __DIR__ . '/../app/config/ai.php';
require __DIR__ . '/../app/includes/ai-client.php';
$config = ai_config();
try {
    if (in_array($config['provider'] ?? '', ['xai','gemini'], true)) {
        $payload = local_payload($config, 'Jawab singkat dalam Bahasa Indonesia.', [['role'=>'user','content'=>'Balas tepat: Koneksi Grok aktif.']]);
        if ($config['provider'] === 'gemini') gemini_payload($config, $payload); else xai_payload($config, $payload);
        echo 'Konfigurasi ' . ($config['provider'] === 'gemini' ? 'Gemini' : 'Grok') . ' tersedia. Kunci tidak ditampilkan.' . PHP_EOL;
        if (in_array('--generate', $argv ?? [], true)) echo response_text(request_ecosystem_ai($config, $payload)) . PHP_EOL;
        else echo 'Belum menghubungi API. Uji nyata (memakai kuota): php tools/check_ai.php --generate' . PHP_EOL;
        exit;
    }
    echo 'Alamat lokal: ' . local_ai_base_url($config) . PHP_EOL;
    echo 'Model: ' . $config['model'] . PHP_EOL;
    $tags = ollama_http($config, 'GET', '/api/tags', null, 5);
    $names = [];
    foreach ($tags['models'] ?? [] as $model) {
        if (is_array($model)) $names[] = $model['name'] ?? $model['model'] ?? '';
    }
    $wanted = strpos($config['model'], ':') === false ? $config['model'] . ':latest' : $config['model'];
    if (!in_array($wanted, $names, true) && !in_array($config['model'], $names, true)) throw new RuntimeException('model_missing');
    echo 'Ollama terhubung dan model tersedia.' . PHP_EOL;
    if (in_array('--generate', $argv ?? [], true)) {
        $payload = local_payload($config, 'Jawab singkat dalam Bahasa Indonesia.', [['role'=>'user','content'=>'Balas tepat: AI lokal aktif.']]);
        echo 'Menguji jawaban model, mohon tunggu...' . PHP_EOL;
        echo response_text(request_ecosystem_ai($config, $payload)) . PHP_EOL;
    } else echo 'Untuk menguji generasi: php tools/check_ai.php --generate' . PHP_EOL;
} catch (Throwable $e) {
    fwrite(STDERR, local_ai_error_message($e) . PHP_EOL);
    exit(1);
}
