<?php
declare(strict_types=1);
require __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../config/ai.php';
require __DIR__ . '/../includes/ai-client.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function reply(array $body, int $status = 200): void {
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    reply(['ok'=>false,'message'=>'Gunakan formulir solusi untuk mengirim keluhan.'], 405);
}
if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 16000) reply(['ok'=>false,'message'=>'Keluhan terlalu panjang.'], 413);
$raw = file_get_contents('php://input', false, null, 0, 16001);
if (strlen($raw) > 16000) reply(['ok'=>false,'message'=>'Keluhan terlalu panjang.'], 413);
$input = json_decode($raw, true);
if (!is_array($input)) reply(['ok'=>false,'message'=>'Data permintaan tidak valid.'], 400);
$token = $input['csrf'] ?? null;
if (!is_string($token) || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $token)) reply(['ok'=>false,'message'=>'Sesi tidak valid. Muat ulang halaman dan coba lagi.'], 419);
foreach (['problem','complaint','context','mode','conversation'] as $key) {
    if (isset($input[$key]) && !is_string($input[$key])) reply(['ok'=>false,'message'=>'Isian formulir tidak valid.'], 422);
}
$problemId = $input['problem'] ?? '';
// Problem bersifat opsional — null berarti rencana umum ekosistem
$problem = $problemId !== '' ? ecosystem_problem($problemId) : null;
$complaint = trim($input['complaint'] ?? '');
$context = trim($input['context'] ?? '');
if (mb_strlen($complaint) < 15 || mb_strlen($complaint) > 2000 || mb_strlen($context) > 300) reply(['ok'=>false,'message'=>'Keluhan harus 15–2.000 karakter dan konteks maksimal 300 karakter.'], 422);
foreach (['baseline','target','days'] as $key) {
    if (!isset($input[$key]) || is_bool($input[$key]) || !is_scalar($input[$key]) || !is_numeric($input[$key])) reply(['ok'=>false,'message'=>'Isi angka skenario dengan benar.'], 422);
}
$baseline = (float)$input['baseline']; $target = (float)$input['target']; $daysValue = (float)$input['days'];
if (!is_finite($baseline) || $baseline <= 0 || $baseline > 1000000 || !is_finite($target) || $target < 0 || $target > 100 || $daysValue < 1 || $daysValue > 365 || floor($daysValue) !== $daysValue) reply(['ok'=>false,'message'=>'Nilai awal harus > 0 sampai 1.000.000, target 0–100%, dan periode 1–365 hari.'], 422);
$mode = $input['mode'] ?? 'ai';
if (!in_array($mode, ['ai','example'], true)) reply(['ok'=>false,'message'=>'Mode tidak valid.'], 422);
$request = ['complaint'=>$complaint,'context'=>$context,'baseline'=>$baseline,'target'=>$target,'days'=>(int)$daysValue];
$conversation = null;
if ($mode === 'ai' && ($input['conversation'] ?? '') !== '') {
    $conversation = conversation_record($input['conversation']);
    if (!$conversation || $conversation['problem'] !== $problemId) reply(['ok'=>false,'message'=>'Percakapan tidak ditemukan. Muat ulang halaman atau mulai chat baru.'], 404);
    if (!isset($input['turn']) || !is_int($input['turn']) || $input['turn'] !== $conversation['turn']) reply(['ok'=>false,'message'=>'Percakapan telah berubah. Muat ulang sebelum menyusun rencana.'], 409);
}
$config = ai_config();
if ($mode === 'ai' && !function_exists('curl_init')) reply(['ok'=>false,'message'=>'Layanan AI belum tersedia di server. Pengelola perlu mengaktifkan ekstensi PHP cURL.'], 503);
$now = time();
if (!reserve_ecosystem_request()) reply(['ok'=>false,'message'=>'Batas 10 permintaan per 10 menit tercapai. Coba lagi nanti.'], 429);
try {
    $solution = $mode === 'ai' ? request_ai_solution($config, $problem, $request, $conversation ?? []) : example_solution_generic($problem);
    $id = bin2hex(random_bytes(12));
    $record = ['id'=>$id,'problem'=>$problemId,'input'=>$request,'solution'=>$solution,'mode'=>$mode,'created_at'=>$now];
    if ($conversation) {
        $record['conversation_id'] = $conversation['id'];
        $record['conversation_turn'] = $conversation['turn'];
        $conversation['solution_id'] = $id;
        save_conversation($conversation);
    }
    $records = array_filter($_SESSION['eco_solutions'] ?? [], static function ($r) use ($now) { return $r['created_at'] > $now - 86400; });
    while (count($records) >= 5) array_shift($records);
    $records[$id] = $record;
    $_SESSION['eco_solutions'] = $records;
    $_SESSION['eco_latest'] = $id;
    reply(['ok'=>true,'record'=>$record,'impact_url'=>'impact.php?solution=' . $id]);
} catch (Throwable $e) {
    reply(['ok'=>false,'message'=>local_ai_error_message($e) . ' [Kode: ' . ai_public_error_code($e) . ']','error_code'=>ai_public_error_code($e)], local_ai_error_status($e));
}
