<section class="section container eco-section" id="problem">
  <div class="glass panel reveal">
    <div class="panel-head">
      <div>
        <span class="eyebrow">01 / PROBLEM</span>
        <h2>Masalah apa yang kamu temui?</h2>
      </div>
    </div>

    <div class="eco-grid">
      <?php foreach (ecosystem_problems() as $id => $problem): ?>
      <a class="eco-card glass" href="solution.php?problem=<?= e($id) ?>">
        <div class="eco-card-top"><span class="badge"><?= icon($problem['icon'], 23) ?></span><span class="eco-category"><?= e($problem['category']) ?></span></div>
        <h3><?= e($problem['title']) ?></h3><p><?= e($problem['description']) ?></p>
        <span class="eco-card-link">Ini masalah saya <?= icon('arrow', 17) ?></span>
      </a>
      <?php endforeach; ?>
    </div>
    <p class="eco-footnote">Belum ada yang persis sama? Pilih topik terdekat dan jelaskan kondisi khususmu pada halaman solusi.</p>
  </div>
</section>
