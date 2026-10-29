<?php
require __DIR__ . '/includes/functions.php';
$pageTitle = 'Neurora — Problem, Solusi AI, dan Dampak Ekosistem';
$activePage = 'home';

include __DIR__ . '/includes/header.php';
include __DIR__ . '/sections/hero.php';       // fitur: hero + bumi 3D interaktif
include __DIR__ . '/sections/problem.php';    // pilihan masalah ekosistem
include __DIR__ . '/sections/services.php';   // pengantar solusi AI
include __DIR__ . '/sections/impact.php';     // pengantar simulasi dampak
include __DIR__ . '/sections/about.php';      // fitur: tentang
include __DIR__ . '/sections/references.php'; // metode dan keterbatasan
include __DIR__ . '/sections/contact.php';    // fitur: form kontak (tabel contact_messages)
include __DIR__ . '/includes/footer.php';
