<?php /** @var string $pageTitle */ ?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle ?? 'Neurora') ?></title>
<meta name="description" content="Neurora: pilih masalah ekosistem, temukan solusi AI, dan simulasikan dugaan dampaknya.">
<meta name="csrf" content="<?= e(csrf_token()) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Plus+Jakarta+Sans:wght@500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="assets/css/ecosystem.css">
<noscript><style>.reveal{opacity:1;transform:none}</style></noscript>
</head>
<body>
<?php include __DIR__ . '/background.php'; ?>
<div class="progress" id="progress"></div>

<header class="site-header" id="siteHeader">
  <div class="container nav glass">
    <a href="index.php#home" class="logo">
      <svg width="30" height="30" viewBox="0 0 32 32"><path d="M3 27V5l8 0 10 14V5h8v22h-8L11 13v14z" fill="#10B981"/><path d="M3 27V5h8v10z" fill="#065f46" opacity=".55"/></svg>
      <span>Neurora</span>
    </a>
    <nav class="nav-links" id="navLinks" aria-label="Navigasi utama">
      <?php foreach (['home'=>['index.php#home','Home'], 'problem'=>['problem.php','Problem'], 'solution'=>['solution.php','Solusi AI'], 'impact'=>['impact.php','Dugaan Dampak'], 'about'=>['index.php#about','Tentang']] as $key=>$nav): ?>
      <a href="<?= e($nav[0]) ?>"<?= ($activePage ?? 'home') === $key ? ' class="active" aria-current="page"' : '' ?>><?= e($nav[1]) ?></a>
      <?php endforeach; ?>
    </nav>
    <div class="nav-actions">
      <button class="icon-btn" id="openSearch" aria-label="Cari"><?= icon('search', 18) ?></button>
      <a href="problem.php" class="btn btn-primary btn-sm">Mulai dari problem <?= icon('arrow', 16) ?></a>
      <button class="icon-btn menu-btn" id="menuBtn" aria-label="Menu" aria-controls="navLinks" aria-expanded="false"><?= icon('menu', 20) ?></button>
    </div>
  </div>
</header>

<!-- Pencarian katalog masalah -->
<div class="modal" id="searchModal" hidden>
  <div class="modal-box glass">
    <div class="search-row"><?= icon('search', 18) ?><input type="search" id="searchInput" placeholder="Cari masalah: udara, air, sampah…" autocomplete="off"><button class="icon-btn" id="closeSearch" aria-label="Tutup"><?= icon('close', 18) ?></button></div>
    <ul class="search-results" id="searchResults"></ul>
  </div>
</div>
<main>
