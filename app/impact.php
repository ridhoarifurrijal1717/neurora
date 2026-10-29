<?php
require __DIR__ . '/includes/functions.php';
$id = is_string($_GET['solution'] ?? null) ? $_GET['solution'] : ($_SESSION['eco_latest'] ?? '');
$record = solution_record($id);
$problem = $record ? ecosystem_problem($record['problem']) : null;
$pageTitle = 'Dugaan Dampak Solusi — Neurora';
$activePage = 'impact';
$pageScript = 'assets/js/impact.js';
include __DIR__ . '/includes/header.php';
?>
<section class="section container eco-section" id="impact">
  <ol class="eco-steps" aria-label="Tahapan"><li class="done"><a href="problem.php">1. Problem</a></li><li class="done"><a href="<?= $record ? 'solution.php?result=' . e($id) : 'solution.php' ?>">2. Solusi AI</a></li><li class="current" aria-current="step">3. Dugaan dampak</li></ol>

  <?php if (!$record): ?>
  <div class="panel glass empty-state"><span class="badge"><?= icon('chart', 24) ?></span><h1>Mulai dari masalah dan solusinya</h1><p>Perkiraan dampak mengikuti solusi yang kamu buat. Hasil tersimpan sementara di sesi browser ini; jika sesi berakhir, buat solusi kembali.</p><a class="btn btn-primary" href="problem.php">Pilih problem <?= icon('arrow', 18) ?></a></div>
  <?php else:
    $in = $record['input'];
    $sol = $record['solution'];
    $hasProblem = $problem !== null;
    $estimate = $hasProblem ? estimate_impact($problem, $in['baseline'], $in['target'], $in['days']) : [
      'total'   => $in['baseline'],
      'benefit' => $in['baseline'] * $in['target'] / 100,
      'remaining' => $in['baseline'] * (1 - $in['target'] / 100),
      'low'     => $in['baseline'] * max(0, $in['target'] * .8) / 100,
      'high'    => $in['baseline'] * min(100, $in['target'] * 1.2) / 100,
      'low_target'  => max(0, $in['target'] * .8),
      'high_target' => min(100, $in['target'] * 1.2),
    ];
    $unit         = $hasProblem ? $problem['unit'] : 'unit';
    $daily        = $hasProblem ? $problem['daily'] : true;
    $impactLabel  = $hasProblem ? $problem['impact_label'] : 'Hasil yang diperkirakan';
    $baselineLabel= $hasProblem ? $problem['baseline_label'] : 'Kondisi awal';
    $targetLabel  = $hasProblem ? $problem['target_label'] : 'Target perbaikan';
    $measurement  = $hasProblem ? $problem['measurement'] : 'Catat kondisi sebelum dan sesudah tindakan menggunakan metode yang konsisten.';
    $steps        = $sol['steps'] ?? [];
    $causes       = $sol['possible_causes'] ?? [];
  ?>

  <div class="page-heading compact">
    <span class="eyebrow">03 / DUGAAN DAMPAK<?= $hasProblem ? ' · ' . e($problem['category']) : '' ?></span>
    <h1>Dari langkah kecil,<br><span class="green">lihat kemungkinan dampaknya.</span></h1>
    <p class="muted">Skenario berdasarkan solusi AI yang kamu buat. Ubah asumsi di bawah dan lihat perhitungan berubah secara langsung.</p>
  </div>

  <div class="impact-banner glass">
    <span class="hyp-chip"><?= $record['mode'] === 'ai' ? 'SOLUSI AI' : 'CONTOH STATIS · BUKAN AI' ?></span>
    <p><b>Simulasi, belum diukur.</b> Target persentase adalah asumsi kamu, bukan prediksi tervalidasi. Angka ini menunjukkan <em>potensi</em> jika tindakan diterapkan konsisten.<?php if (!empty($record['conversation_turn'])): ?> Rencana memakai konteks <?= (int)$record['conversation_turn'] ?> tanya jawab.<?php endif; ?></p>
  </div>

  <!-- ① KALKULATOR SKENARIO -->
  <div class="panel glass impact-workspace" id="impactCalculator" data-daily="<?= $daily ? '1' : '0' ?>" data-unit="<?= e($unit) ?>">
    <div class="impact-controls"><h2>Atur skenariomu</h2><form id="impactForm">
      <label><?= e($baselineLabel) ?> (<?= e($unit) ?>)<input id="impactBaseline" type="number" min="0.01" max="1000000" step="0.01" required value="<?= e($in['baseline']) ?>"></label>
      <label><?= e($targetLabel) ?> (%)<input id="impactTarget" type="number" min="0" max="100" step="0.1" required value="<?= e($in['target']) ?>"></label>
      <label>Periode evaluasi (hari)<input id="impactDays" type="number" min="1" max="365" step="1" required value="<?= e($in['days']) ?>"></label>
      <p class="field-help">Mengubah skenario menghitung ulang angka. Rencana tindakan tetap memakai konteks awal saat dibuat.</p>
      <button type="button" class="btn btn-ghost btn-sm" id="resetImpact">Kembalikan asumsi awal</button>
    </form><p id="impactError" class="form-status err" role="status"></p></div>

    <div class="impact-numbers" id="impactNumbers" aria-live="polite">
      <span class="eyebrow">HIPOTESIS DAMPAK</span>
      <h2><?= e($impactLabel) ?></h2>
      <div class="impact-big"><strong id="impactBenefit"><?= e(number_format($estimate['benefit'], 1, ',', '.')) ?></strong><span><?= e($unit) ?> <small id="impactPeriod"><?= $daily ? 'dalam ' . $in['days'] . ' hari' : 'target pada hari ke-' . $in['days'] ?></small></span></div>
      <div class="scenario-bar" aria-hidden="true"><span id="scenarioBar" style="width:<?= e($in['target']) ?>%"></span></div>
      <div class="impact-mini-grid">
        <div><small><?= $daily ? 'Total kondisi awal' : 'Kondisi tersedia' ?></small><b id="impactTotal"><?= e(number_format($estimate['total'], 1, ',', '.')) ?> <?= e($unit) ?></b></div>
        <div><small><?= $daily ? 'Sisa setelah target' : 'Belum ditangani' ?></small><b id="impactRemaining"><?= e(number_format($estimate['remaining'], 1, ',', '.')) ?> <?= e($unit) ?></b></div>
      </div>
      <div class="sensitivity"><b>Jika target ±20%</b><p id="impactRange"><?= e(number_format($estimate['low'], 1, ',', '.')) ?>–<?= e(number_format($estimate['high'], 1, ',', '.')) ?> <?= e($unit) ?></p><small>Uji sensitivitas asumsi, bukan rentang probabilitas hasil.</small></div>
    </div>
  </div>

  <!-- ② VISUALISASI SOLUSI AI -->
  <?php if ($record['mode'] === 'ai' && !empty($steps)): ?>
  <div class="impact-viz-section">
    <div class="panel glass impact-chart-panel">
      <span class="eyebrow">VISUALISASI DAMPAK</span>
      <h2>Proyeksi perbaikan per tahapan</h2>
      <p class="muted" style="font-size:14px;margin-bottom:20px">Setiap langkah solusi AI diperkirakan berkontribusi terhadap total target. Ini ilustrasi bertahap, bukan ukuran efektivitas tiap langkah.</p>
      <div class="chart-wrap"><canvas id="impactChart" aria-label="Grafik proyeksi dampak per langkah solusi"></canvas></div>
    </div>

    <div class="panel glass cause-panel">
      <span class="eyebrow">KEMUNGKINAN PENYEBAB</span>
      <h2>Yang perlu kamu periksa</h2>
      <ul class="cause-list">
        <?php foreach ($causes as $i => $cause): ?>
        <li class="cause-item">
          <span class="cause-num"><?= str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
          <p><?= e($cause) ?></p>
        </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>

  <div class="panel glass steps-visual" id="stepsTimeline">
    <span class="eyebrow">RENCANA TINDAKAN · DARI SOLUSI AI</span>
    <h2>Langkah dan kontribusinya</h2>
    <p class="muted" style="font-size:14px;margin:0 0 28px">Setiap langkah mewakili tahapan menuju target. Persentase adalah estimasi kontribusi relatif, bukan hasil terukur.</p>
    <div class="steps-bars">
      <?php
        $count = count($steps);
        // Bagi target secara progresif: langkah awal lebih kecil, langkah akhir lebih besar
        $weights = [];
        for ($i = 0; $i < $count; $i++) $weights[] = $i + 1;
        $wSum = array_sum($weights);
        $cumulative = 0;
      ?>
      <?php foreach ($steps as $i => $step):
        $pct = round($weights[$i] / $wSum * $in['target'], 1);
        $cumulative += $pct;
        $barW = round($weights[$i] / $wSum * 100);
      ?>
      <div class="step-bar-row">
        <div class="step-bar-meta">
          <span class="step-bar-num"><?= str_pad((string)($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
          <div>
            <b><?= e($step['title']) ?></b>
            <small><?= e($step['timing']) ?></small>
          </div>
          <span class="step-bar-pct">+<?= $pct ?>%</span>
        </div>
        <div class="step-bar-track">
          <div class="step-bar-fill" style="width:<?= $barW ?>%;transition-delay:<?= $i * 0.12 ?>s"></div>
          <span class="step-bar-label"><?= e(number_format($in['baseline'] * ($daily ? $in['days'] : 1) * $cumulative / 100, 1, ',', '.')) ?> <?= e($unit) ?></span>
        </div>
        <p class="step-bar-action"><?= e($step['action']) ?></p>
      </div>
      <?php endforeach; ?>
    </div>
    <p class="field-help" style="margin-top:20px">Persentase dihitung secara proporsional dari target skenario kamu (<?= e($in['target']) ?>%). Angka bukan ukuran efektivitas ilmiah setiap langkah.</p>
  </div>
  <?php endif; ?>

  <!-- ③ RUMUS & VALIDASI -->
  <div class="result-columns method-panels">
    <div class="panel glass">
      <span class="eyebrow">RUMUS TERBUKA</span>
      <h2>Angka ini dari mana?</h2>
      <p class="formula"><?= $daily ? 'Nilai awal per hari × hari × target ÷ 100' : 'Nilai awal × target ÷ 100' ?></p>
      <p id="impactFormula"></p>
      <?php if (!$daily): ?><p class="field-help">Nilai awal dihitung sekali. Periode digunakan sebagai tenggat evaluasi, bukan pengali.</p><?php endif; ?>
      <p class="field-help">Target dan variasi skenario tidak berasal dari studi efektivitas; ini asumsi yang ingin kamu uji.</p>
    </div>
    <div class="panel glass">
      <span class="eyebrow">VALIDASI DI LAPANGAN</span>
      <h2>Ukur sebelum dan sesudah</h2>
      <p><?= e($measurement) ?></p>
      <?php if (!empty($sol['measurement_plan'])): ?>
      <ul class="eco-list"><?php foreach ($sol['measurement_plan'] as $m): ?><li><?= e($m) ?></li><?php endforeach; ?></ul>
      <?php endif; ?>
    </div>
  </div>

  <!-- ④ RINGKASAN SOLUSI -->
  <div class="panel glass impact-recap">
    <span class="eyebrow">SOLUSI YANG DIUJI</span>
    <h2>Rencana di balik skenario ini</h2>
    <p><?= e($sol['impact_hypothesis']) ?></p>
    <?php if (!empty($sol['caution'])): ?><p class="service-note"><?= e($sol['caution']) ?></p><?php endif; ?>
    <div class="form-actions">
      <a class="btn btn-ghost" href="solution.php?result=<?= e($id) ?>">← Kembali ke solusi</a>
      <a class="btn btn-primary" href="<?= $hasProblem ? 'problem.php' : 'solution.php' ?>">Coba masalah lain <?= icon('arrow', 16) ?></a>
    </div>
  </div>

  <script id="impactData" type="application/json"><?= json_encode([
    'steps'    => array_column($steps, 'title'),
    'target'   => (float)$in['target'],
    'baseline' => (float)$in['baseline'],
    'days'     => (int)$in['days'],
    'daily'    => $daily,
    'unit'     => $unit,
    'weights'  => array_map(fn($i) => $i + 1, range(0, count($steps) - 1)),
  ], JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?></script>
  <noscript><p class="service-note">Aktifkan JavaScript untuk kalkulator skenario dan grafik dampak.</p></noscript>
  <?php endif; ?>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
