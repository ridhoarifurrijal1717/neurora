</main>
<footer class="container footer glass">
  <a href="index.php#home" class="logo">
    <svg width="26" height="26" viewBox="0 0 32 32"><path d="M3 27V5l8 0 10 14V5h8v22h-8L11 13v14z" fill="#10B981"/></svg><span>Neurora</span>
  </a>
  <nav class="footer-links"><a href="problem.php">Problem</a><a href="solution.php">Solusi AI</a><a href="impact.php">Dugaan Dampak</a><a href="index.php#references">Metode</a><a href="index.php#contact">Kontak</a></nav>
  <span class="copy">© <?= date('Y') ?> Neurora. Created By RWS</span>
  <span class="tag"><?= icon('leaf', 16) ?> Small Steps, Greener Earth</span>
</footer>

<!-- Bottom nav (mobile) -->
<nav class="bottom-nav glass" aria-label="Navigasi mobile">
  <?php foreach (['home'=>['index.php#home','home','Home'], 'problem'=>['problem.php','leaf','Problem'], 'solution'=>['solution.php','spark','Solusi AI'], 'impact'=>['impact.php','chart','Dampak']] as $key=>$nav): ?>
  <a href="<?= e($nav[0]) ?>"<?= ($activePage ?? 'home') === $key ? ' class="active" aria-current="page"' : '' ?>><?= icon($nav[1], 20) ?><small><?= e($nav[2]) ?></small></a>
  <?php endforeach; ?>
</nav>

<?php if (($activePage ?? 'home') === 'home'): ?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/three@0.128.0/examples/js/controls/OrbitControls.js"></script>
<?php endif; ?>
<script src="assets/js/main.js"></script>
<script src="assets/js/forest.js"></script>
<?php if (($activePage ?? 'home') === 'home'): ?><script src="assets/js/globe.js"></script><?php endif; ?>
<?php if (isset($pageScript)): ?><script src="<?= e($pageScript) ?>"></script><?php endif; ?>
</body>
</html>
