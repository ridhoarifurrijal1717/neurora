<?php
require __DIR__ . '/includes/functions.php';
require __DIR__ . '/config/ai.php';
$resultId = is_string($_GET['result'] ?? null) ? $_GET['result'] : (isset($_GET['problem']) || isset($_GET['conversation']) || isset($_GET['new']) ? '' : ($_SESSION['eco_latest'] ?? ''));
$record = solution_record($resultId);
$problemId = $record['problem'] ?? (is_string($_GET['problem'] ?? null) ? $_GET['problem'] : '');
$startNew = ($_GET['new'] ?? '') === '1';
$chatId = $startNew ? '' : (is_string($_GET['conversation'] ?? null) ? $_GET['conversation'] : ($record ? ($record['conversation_id'] ?? '') : ($_SESSION['eco_chat_latest'][$problemId] ?? '')));
$conversation = $chatId !== '' ? conversation_record($chatId) : null;
if ($problemId === '' && $conversation) $problemId = $conversation['problem'];
if ($conversation && $conversation['problem'] !== $problemId) $conversation = null;
$problem = $problemId !== '' ? ecosystem_problem($problemId) : null;
$input = $record['input'] ?? [];
$pageTitle = 'Solusi AI untuk Ekosistem — Neurora';
$activePage = 'solution';
$pageScript = 'assets/js/solution.js';
$localConfig = ai_config();
$configured = function_exists('curl_init');
$isGrok = ($localConfig['provider'] ?? 'ollama') === 'xai';
$isGemini = ($localConfig['provider'] ?? '') === 'gemini';
$allProblems = ecosystem_problems();
include __DIR__ . '/includes/header.php';
?>
<section class="section container eco-section">
  <ol class="eco-steps" aria-label="Tahapan"><li class="done"><a href="problem.php">1. Problem</a></li><li class="current" aria-current="step">2. Solusi AI</li><li>3. Dugaan dampak</li></ol>

  <div class="page-heading compact">
    <span class="eyebrow">02 / SOLUSI AI<?= $problem ? ' · ' . e($problem['category']) : '' ?></span>
    <h1>Tanyakan masalahmu.<br><span class="green">Cari solusinya bersama AI.</span></h1>
    <p class="muted"><?= $problem ? 'Kamu memilih kategori ' . e($problem['category']) . '. Tanyakan penyebab, minta solusi, atau lanjutkan obrolan untuk memperjelas kondisi di sekitarmu.' : 'Langsung tulis pertanyaanmu di bawah, atau pilih kategori problem ekosistem agar AI bisa memberi konteks yang lebih spesifik.' ?></p>
  </div>

  <div class="solution-layout">
    <aside class="panel glass selected-problem">
      <?php if ($problem): ?>
        <span class="badge"><?= icon($problem['icon'], 24) ?></span>
        <span class="eyebrow">PROBLEM PILIHANMU</span>
        <h2><?= e($problem['title']) ?></h2>
        <p><?= e($problem['description']) ?></p>
        <a class="text-link" href="problem.php">← Ganti problem</a>
      <?php else: ?>
        <span class="badge"><?= icon('spark', 24) ?></span>
        <span class="eyebrow">PROBLEM <span class="optional"></span></span>
        <h2>Belum ada kategori dipilih</h2>
        <p>Kamu bisa langsung bertanya tanpa memilih kategori. Pilih kategori jika ingin AI memberi konteks ekosistem yang lebih spesifik.</p>
        <div class="problem-select-wrap">
          <label for="problemSelect" class="problem-select-label">Pilih kategori</label>
          <select id="problemSelect" class="problem-select">
            <option value="">— Tanpa kategori —</option>
            <?php foreach ($allProblems as $pid => $p): ?>
              <option value="<?= e($pid) ?>"<?= $pid === $problemId ? ' selected' : '' ?>><?= e($p['category']) ?> · <?= e($p['title']) ?></option>
            <?php endforeach; ?>
          </select>
          <a id="problemSelectLink" class="text-link" href="problem.php" style="display:<?= $problemId ? 'inline-flex' : 'none' ?>">Lihat detail problem <?= icon('arrow', 14) ?></a>
        </div>
      <?php endif; ?>
      <div class="side-note"><b>Tanya dulu, susun langkahnya</b><p>Mulai lewat chat. Setelah jawabannya sesuai, susun rencana solusi dari percakapanmu, lalu lihat dugaan dampaknya.</p></div>
    </aside>

    <div class="solution-main">
    <section class="panel glass chat-panel" aria-labelledby="chatHeading">
      <div class="chat-header"><div><span class="eyebrow">TANYA NEURORA AI · <?= $isGrok ? 'GROK' : ($isGemini ? 'GEMINI' : 'LOKAL') ?></span><h2 id="chatHeading">Obrolan<?= $problem ? ' tentang ' . e(mb_strtolower($problem['category'])) : '' ?></h2></div><button type="button" id="newChatBtn" class="btn btn-ghost btn-sm">Chat baru</button></div>
      <div class="chat-topic" id="chatTopic"><?php if ($problem): ?><?= icon($problem['icon'], 15) ?> <?= e($problem['category']) ?><?php else: ?><?= icon('spark', 15) ?> Ekosistem umum<?php endif; ?> <span id="chatCount">0 / 12 tanya jawab</span></div>
      <?php if (!$configured): ?><p class="service-note">PHP cURL belum aktif. Pengelola perlu mengaktifkannya untuk menghubungkan AI.</p><?php endif; ?>
      <?php if ($chatId !== '' && !$conversation): ?><p class="service-note">Riwayat chat tidak tersedia dalam sesi ini. Mulai percakapan baru untuk melanjutkan.</p><?php endif; ?>
      <div class="chat-scroll" id="chatScroll">
        <div class="chat-welcome" id="chatWelcome"><span class="badge"><?= icon('spark', 20) ?></span><div><b>Mulai dari pertanyaanmu</b><p><?= $problem ? 'Ceritakan masalah ' . e(mb_strtolower($problem['category'])) . ' yang kamu hadapi, lalu minta penjelasan atau solusi.' : 'Ceritakan masalah ekosistem yang kamu hadapi — udara, air, sampah, tanah, energi, atau ruang hijau. Minta penjelasan atau solusi langsung.' ?> Pertanyaan berikutnya akan memakai konteks obrolan ini.</p><small>Panduan Neurora · jawaban AI muncul setelah kamu mengirim pertanyaan.</small></div></div>
        <div id="chatMessages" class="chat-messages" role="log" aria-label="Riwayat tanya jawab dengan AI" aria-live="polite" aria-relevant="additions"></div>
      </div>
      <div id="chatSuggestions" class="chat-suggestions" aria-label="Ide pertanyaan">
        <button type="button" class="question-chip">Apa penyebab masalah ini yang perlu saya periksa?</button>
        <button type="button" class="question-chip">Tolong beri solusi sederhana yang bisa saya mulai hari ini.</button>
        <button type="button" class="question-chip">Bagaimana cara mengukur apakah solusinya berhasil?</button>
      </div>
      <form id="chatForm" class="chat-composer" data-timeout-ms="<?= ((int)$localConfig['timeout'] + 30) * 1000 ?>">
        <label for="chatQuestion">Pertanyaan atau permintaan solusi</label>
        <textarea id="chatQuestion" name="message" rows="3" required minlength="1" maxlength="2000" placeholder="<?= $problem ? 'Tanyakan masalah ' . e(mb_strtolower($problem['category'])) . ' dan solusi yang kamu butuhkan…' : 'Tulis masalah ekosistemmu di sini — langsung saja, tanpa perlu pilih kategori dulu…' ?>"></textarea>
        <div class="chat-composer-actions"><small></small><span id="chatCharCount">0 / 2.000</span><button type="submit" id="sendChatBtn" class="btn btn-primary"><?= icon('arrow', 16) ?> Kirim pertanyaan</button></div>
        <p id="chatStatus" class="form-status" role="status" aria-live="polite"></p>
      </form>
      <div class="chat-next" id="chatNext" hidden><p>Sudah menemukan arah solusinya? Jadikan obrolan ini rencana yang bisa diuji.</p><button type="button" class="btn btn-ghost btn-sm" id="useChatBtn">Susun rencana dari chat <?= icon('arrow', 16) ?></button></div>
      <p class="chat-privacy"><?= ($isGrok || $isGemini) ? 'Pertanyaan, konteks, dan riwayat dikirim ke ' . ($isGrok ? 'xAI/Grok' : 'Google/Gemini') . ' saat kamu menekan Kirim.' : 'Pertanyaan, konteks, dan riwayat diproses AI lokal pada komputer server.' ?> Jawaban perlu ditinjau sesuai kondisi di lokasi.</p>
    </section>

    <section class="panel glass plan-builder" id="planBuilder" aria-labelledby="planHeading">
      <span class="eyebrow">DARI OBROLAN KE TINDAKAN</span><h2 id="planHeading">Rencana solusi &amp; dugaan dampak</h2><p class="plan-intro" id="planIntro">Gunakan percakapanmu atau isi keluhan langsung. Lengkapi asumsi untuk menyusun rencana tindakan dan simulasi dampaknya.</p>
      <details id="planDetails"<?= $record ? ' open' : '' ?>><summary>Lengkapi ringkasan dan asumsi dampak</summary>
      <form id="solutionForm">
        <input type="hidden" name="problem" id="problemField" value="<?= e($problemId) ?>">
        <label for="complaint">Ringkasan masalahmu <span class="required">*</span></label>
        <textarea id="complaint" name="complaint" rows="4" minlength="15" maxlength="2000" required><?= e($input['complaint'] ?? ($problem['prompt'] ?? '')) ?></textarea>
        <small class="field-help"></small>
        <label for="context">Konteks tempat <span class="optional"></span></label>
        <input id="context" name="context" maxlength="300" placeholder="Contoh: permukiman padat, 20 rumah, dekat sungai" value="<?= e($input['context'] ?? ($conversation['context'] ?? '')) ?>">
        <fieldset class="scenario-fields"><legend>Asumsi awal untuk simulasi dampak</legend><p class="field-help">Isi dari catatanmu, atau ubah angka contoh di bawah. Target adalah asumsi yang ingin diuji.</p>
          <div class="eco-form-grid">
            <label id="baselineLabel"><?= $problem ? e($problem['baseline_label']) : 'Kondisi awal' ?> <?= $problem ? '(' . e($problem['unit']) . ')' : '' ?><input type="number" name="baseline" id="baselineInput" min="0.01" max="1000000" step="0.01" required value="<?= e($input['baseline'] ?? ($problem['baseline'] ?? 10)) ?>"></label>
            <label><?= $problem ? e($problem['target_label']) : 'Target perbaikan' ?> (%)<input type="number" name="target" min="0" max="100" step="0.1" required value="<?= e($input['target'] ?? ($problem['target'] ?? 30)) ?>"></label>
            <label>Periode evaluasi (hari)<input type="number" name="days" min="1" max="365" step="1" required value="<?= e($input['days'] ?? 30) ?>"></label>
          </div>
        </fieldset>
        <?php if (!$configured): ?><p class="service-note">PHP cURL belum aktif. Pengelola perlu mengaktifkannya; kamu tetap bisa mencoba contoh alur.</p><?php endif; ?>
        <p class="field-help"><?= ($isGrok || $isGemini) ? 'Rencana dibuat melalui ' . ($isGrok ? 'Grok/xAI' : 'Gemini/Google') . ' dari keluhan, riwayat chat, dan asumsi skenario. Data dikirim saat rencana dibuat.' : 'Rencana dibuat oleh AI lokal dari keluhan, riwayat chat, dan asumsi skenario.' ?></p>
        <div class="form-actions"><button class="btn btn-primary" type="submit" id="generateBtn"><?= icon('spark', 18) ?> Buat solusi AI</button><button class="btn btn-ghost" type="button" id="exampleBtn">Lihat contoh alur</button></div>
        <p id="solutionStatus" class="form-status" role="status" aria-live="polite"></p>
      </form>
      </details>
    </section>
    </div>
  </div>

  <section class="panel glass ai-result" id="solutionResult" hidden aria-labelledby="resultHeading">
    <div class="result-heading"><span class="chip" id="resultMode"></span><h2 id="resultHeading">Rencana tindakanmu</h2><p id="resultSummary"></p></div>
    <div class="result-columns"><div><h3>Kemungkinan penyebab</h3><ul id="resultCauses" class="eco-list"></ul></div><div><h3>Dugaan perubahan</h3><p id="resultHypothesis"></p></div></div>
    <ol id="resultSteps" class="action-steps"></ol>
    <h3>Cara memeriksa hasilnya</h3><ul id="resultMeasurements" class="eco-list"></ul>
    <p id="resultCaution" class="service-note"></p>
    <div class="result-next"><p>Solusinya sudah ada. Sekarang, coba perkiraan dampaknya.</p><a id="impactLink" href="impact.php" class="btn btn-primary">Lihat dugaan dampak <?= icon('arrow', 18) ?></a></div>
  </section>
  <script id="initialSolution" type="application/json"><?= json_encode($record, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?></script>
  <script id="initialConversation" type="application/json"><?= json_encode($conversation ? public_conversation($conversation) : null, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?></script>
  <script id="initialProblemId" type="application/json"><?= json_encode($problemId, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?></script>
  <script id="allProblems" type="application/json"><?= json_encode(array_map(fn($p) => ['baseline_label'=>$p['baseline_label'],'unit'=>$p['unit'],'baseline'=>$p['baseline'],'target'=>$p['target'],'target_label'=>$p['target_label'],'category'=>$p['category'],'icon'=>$p['icon'],'prompt'=>$p['prompt']], $allProblems), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?></script>
  <noscript><p class="service-note">Aktifkan JavaScript untuk chat, mengirim keluhan, dan menampilkan solusi AI.</p></noscript>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
