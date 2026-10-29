<?php
declare(strict_types=1);
require __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../config/ai.php';
require __DIR__ . '/../includes/ai-client.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function chat_reply(array $body, int $status = 200): void {
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    chat_reply(['ok'=>false,'message'=>'Kirim pertanyaan melalui kolom chat.'], 405);
}
if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 14000) chat_reply(['ok'=>false,'message'=>'Pertanyaan terlalu panjang.'], 413);
$raw = file_get_contents('php://input', false, null, 0, 14001);
if ($raw === false || strlen($raw) > 14000) chat_reply(['ok'=>false,'message'=>'Permintaan terlalu panjang.'], 413);
$input = json_decode($raw, true);
if (!is_array($input)) chat_reply(['ok'=>false,'message'=>'Data pertanyaan tidak valid.'], 400);
$token = $input['csrf'] ?? null;
if (!is_string($token) || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $token)) chat_reply(['ok'=>false,'message'=>'Sesi tidak valid. Muat ulang halaman dan coba lagi.'], 419);
foreach (['problem','message','conversation','context','solution_id'] as $key) {
    if (isset($input[$key]) && !is_string($input[$key])) chat_reply(['ok'=>false,'message'=>'Data pertanyaan tidak valid.'], 422);
}
$problemId = $input['problem'] ?? '';
// Problem bersifat opsional — null berarti percakapan umum ekosistem
$problem = $problemId !== '' ? ecosystem_problem($problemId) : null;
$message = trim($input['message'] ?? '');
$context = trim($input['context'] ?? '');
if ($message === '' || mb_strlen($message) > 2000 || mb_strlen($context) > 300) chat_reply(['ok'=>false,'message'=>'Isi pertanyaan 1–2.000 karakter dan konteks tempat maksimal 300 karakter.'], 422);
$conversationId = $input['conversation'] ?? '';
$conversation = $conversationId !== '' ? conversation_record($conversationId) : null;
if ($conversationId !== '' && (!$conversation || $conversation['problem'] !== $problemId)) chat_reply(['ok'=>false,'message'=>'Percakapan tidak ditemukan dalam sesi ini. Mulai chat baru.'], 404);
$turn = $input['turn'] ?? null;
if (!is_int($turn) || $turn < 0 || $turn > 12) chat_reply(['ok'=>false,'message'=>'Versi percakapan tidak valid. Muat ulang halaman.'], 422);
if ($turn !== ($conversation['turn'] ?? 0)) chat_reply(['ok'=>false,'message'=>'Percakapan telah berubah di tab lain. Muat ulang sebelum mengirim pertanyaan.'], 409);
if ($turn >= 12) chat_reply(['ok'=>false,'message'=>'Percakapan ini sudah mencapai 12 tanya jawab. Mulai chat baru untuk melanjutkan.'], 422);
$planId = $input['solution_id'] ?? ($conversation['solution_id'] ?? '');
$plan = $planId !== '' ? solution_record($planId) : null;
if ($plan && $plan['mode'] !== 'ai') chat_reply(['ok'=>false,'message'=>'Rencana yang dipilih tidak valid untuk konteks chat ini.'], 404);
$config = ai_config();
if (!function_exists('curl_init')) chat_reply(['ok'=>false,'message'=>'Layanan AI belum tersedia di server. Pengelola perlu mengaktifkan PHP cURL.'], 503);
if (!reserve_ecosystem_request()) chat_reply(['ok'=>false,'message'=>'Batas 10 permintaan per 10 menit tercapai. Coba lagi nanti.'], 429);
try {
    $answer = request_ai_chat($config, $problem, $message, $conversation ?? [], $context, $plan);
    $now = time();
    if (!$conversation) $conversation = ['id'=>bin2hex(random_bytes(12)),'problem'=>$problemId,'messages'=>[],'api_history'=>[],'turn'=>0,'created_at'=>$now];
    $conversation['messages'][] = ['role'=>'user','content'=>$message];
    $conversation['messages'][] = ['role'=>'assistant','content'=>$answer['text']];
    $conversation['api_history'] = $conversation['messages'];
    $conversation['context'] = $context;
    if ($plan) $conversation['solution_id'] = $plan['id']; else unset($conversation['solution_id']);
    $conversation['turn']++;
    save_conversation($conversation);
    chat_reply(['ok'=>true,'conversation'=>public_conversation($conversation)]);
} catch (Throwable $e) {
    chat_reply(['ok'=>false,'message'=>local_ai_error_message($e) . ' [Kode: ' . ai_public_error_code($e) . ']','error_code'=>ai_public_error_code($e)], local_ai_error_status($e));
}
