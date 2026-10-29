<?php
/* ===== Latar hutan & ekosistem (dibuat oleh PHP, tanpa gambar eksternal) =====
 * Lapisan dari jauh ke dekat: langit → gunung + kincir angin → hutan jauh →
 * bukit tengah + danau → pohon tepi → kabut → daun & semak depan → partikel.
 */
if (!function_exists('bg_pine')) {
    function bg_pine(float $x, float $base, float $h, float $w): string {
        $d = '';
        $n = 4;
        for ($i = 0; $i < $n; $i++) {
            $ay = $base - $h + $i * ($h * 0.55) / $n;
            $by = $ay + $h * 0.45;
            $hw = ($w / 2) * (0.45 + 0.55 * ($i + 1) / $n);
            $d .= sprintf('M%.1f %.1fL%.1f %.1fL%.1f %.1fZ', $x, $ay, $x + $hw, $by, $x - $hw, $by);
        }
        $d .= sprintf('M%.1f %.1fh%.1fv%.1fh%.1fZ', $x - $w * 0.04, $base - $h * 0.14, $w * 0.08, $h * 0.14, -$w * 0.08);
        return $d;
    }

    function bg_round(float $x, float $base, float $h, float $w): string {
        $r = $w / 2;
        $cy = $base - $h * 0.62;
        $d = '';
        foreach ([[0, 0, 1], [-0.55, 0.28, 0.72], [0.55, 0.28, 0.72], [0, -0.45, 0.7]] as $c) {
            $rr = $r * $c[2];
            $cx = $x + $c[0] * $r;
            $cyy = $cy + $c[1] * $r;
            $d .= sprintf('M%.1f %.1fa%.1f %.1f 0 1 1 %.1f 0a%.1f %.1f 0 1 1 %.1f 0Z', $cx - $rr, $cyy, $rr, $rr, 2 * $rr, $rr, $rr, -2 * $rr);
        }
        $d .= sprintf('M%.1f %.1fh%.1fv%.1fh%.1fZ', $x - $w * 0.05, $base - $h * 0.4, $w * 0.1, $h * 0.4, -$w * 0.1);
        return $d;
    }

    function bg_forest(int $seed, float $x0, float $x1, float $base, float $hmin, float $hmax, float $step, float $round = 0.0): string {
        mt_srand($seed);
        $d = '';
        for ($x = $x0; $x <= $x1; $x += $step * (0.7 + mt_rand(0, 60) / 100)) {
            $h = $hmin + mt_rand(0, 1000) / 1000 * ($hmax - $hmin);
            $w = $h * (0.34 + mt_rand(0, 14) / 100);
            $b = $base + mt_rand(-12, 12);
            $d .= (mt_rand(0, 100) / 100 < $round) ? bg_round($x, $b, $h, $w * 1.25) : bg_pine($x, $b, $h, $w);
        }
        return $d;
    }

    function bg_leaf(float $x, float $y, float $rot, float $s, float $dur): string {
        return sprintf(
            '<g transform="translate(%.0f %.0f) rotate(%.0f) scale(%.2f)"><g>'
            . '<path d="M0 0C40 -60 150 -75 230 -25C160 40 60 50 0 0Z" fill="url(#gLeaf)"/>'
            . '<path d="M0 0C70 -15 150 -22 220 -25" stroke="rgba(255,255,255,.4)" stroke-width="2" fill="none"/>'
            . '<animateTransform attributeName="transform" type="rotate" values="-4 0 0;4 0 0;-4 0 0" dur="%.1fs" repeatCount="indefinite"/>'
            . '</g></g>',
            $x, $y, $rot, $s, $dur
        );
    }

    function bg_turbine(float $x, float $y, float $s, float $dur): string {
        $blade = '<path d="M-1.5 0L0 -34L1.5 0Z"/>';
        return sprintf(
            '<g transform="translate(%.0f %.0f) scale(%.2f)" fill="#f4faf7"><path d="M-1.6 0L-.7 -62L.7 -62L1.6 0Z"/>'
            . '<g transform="translate(0 -62)"><circle r="2.4"/><g>%s<g transform="rotate(120)">%s</g><g transform="rotate(240)">%s</g>'
            . '<animateTransform attributeName="transform" type="rotate" from="0" to="360" dur="%.0fs" repeatCount="indefinite"/></g></g></g>',
            $x, $y, $s, $blade, $blade, $blade, $dur
        );
    }
}
$VB = 'viewBox="0 0 1600 900" preserveAspectRatio="xMidYMax slice"';
?>
<div class="bg-scene" aria-hidden="true">
  <div class="bg-sky"></div>
  <div class="bg-sun"></div>
  <div class="bg-rays"></div>

  <!-- 1. gunung jauh + kincir angin -->
  <svg class="bg-layer" data-depth="0.02" <?= $VB ?>>
    <defs><linearGradient id="gFar" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#b9d6ca"/><stop offset="1" stop-color="#dbece5"/></linearGradient></defs>
    <path d="M0 520C140 430 260 470 400 420S650 360 820 440S1130 380 1300 430S1500 440 1600 410V900H0Z" fill="url(#gFar)"/>
    <?= bg_turbine(430, 440, 0.9, 11) ?>
    <?= bg_turbine(1010, 432, 1.15, 9) ?>
    <?= bg_turbine(1290, 424, 0.8, 13) ?>
  </svg>

  <!-- 2. hutan pinus jauh -->
  <svg class="bg-layer" data-depth="0.04" <?= $VB ?>>
    <path d="<?= bg_forest(11, -20, 1640, 585, 70, 135, 24, 0.1) ?>" fill="#a2cabb" opacity=".78"/>
    <path d="M0 585H1600V900H0Z" fill="#a2cabb" opacity=".78"/>
  </svg>

  <div class="mist m1"></div>

  <!-- 3. bukit tengah + danau -->
  <svg class="bg-layer" data-depth="0.08" <?= $VB ?>>
    <defs>
      <linearGradient id="gMid" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#79b9a0"/><stop offset="1" stop-color="#5aa386"/></linearGradient>
      <linearGradient id="gLake" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#d9f1ea"/><stop offset="1" stop-color="#8fcdb9"/></linearGradient>
    </defs>
    <path d="M0 690C250 620 520 680 820 640S1350 610 1600 670V900H0Z" fill="#8fc4ae"/>
    <path d="<?= bg_forest(23, -20, 1640, 690, 105, 190, 34, 0.28) ?>" fill="url(#gMid)" opacity=".9"/>
    <ellipse cx="800" cy="790" rx="560" ry="46" fill="url(#gLake)" opacity=".85"/>
    <g stroke="#fff" stroke-width="2" stroke-linecap="round" opacity=".55">
      <path d="M560 785h90"><animate attributeName="opacity" values=".1;.8;.1" dur="4s" repeatCount="indefinite"/></path>
      <path d="M760 798h130"><animate attributeName="opacity" values=".8;.1;.8" dur="5s" repeatCount="indefinite"/></path>
      <path d="M980 782h80"><animate attributeName="opacity" values=".2;.9;.2" dur="3.5s" repeatCount="indefinite"/></path>
    </g>
  </svg>

  <!-- 4. pohon besar di tepi kiri & kanan -->
  <svg class="bg-layer" data-depth="0.13" <?= $VB ?>>
    <defs><linearGradient id="gNear" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#3a9877"/><stop offset="1" stop-color="#14503f"/></linearGradient></defs>
    <path d="<?= bg_forest(37, -60, 430, 935, 400, 700, 120, 0.4) ?>" fill="url(#gNear)"/>
    <path d="<?= bg_forest(41, 1170, 1660, 935, 380, 680, 120, 0.4) ?>" fill="url(#gNear)"/>
  </svg>

  <div class="mist m2"></div>

  <!-- 5. daun besar & semak depan -->
  <svg class="bg-layer" data-depth="0.19" <?= $VB ?>>
    <defs><linearGradient id="gLeaf" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#4ade80"/><stop offset="1" stop-color="#047857"/></linearGradient></defs>
    <?= bg_leaf(-30, 180, 25, 1.1, 6) ?>
    <?= bg_leaf(-20, 330, -8, 0.9, 7.5) ?>
    <?= bg_leaf(1630, 210, 160, 1.15, 6.5) ?>
    <?= bg_leaf(1640, 400, 190, 0.85, 8) ?>
    <?= bg_leaf(-40, 760, -40, 1.3, 7) ?>
    <?= bg_leaf(1650, 780, 220, 1.3, 6.8) ?>
    <?php mt_srand(5); ?>
    <g fill="#0f4a3a" opacity=".92">
      <?php for ($x = -40; $x < 1660; $x += 80): ?>
        <ellipse cx="<?= $x + mt_rand(-20, 20) ?>" cy="<?= 925 + mt_rand(-10, 10) ?>" rx="<?= mt_rand(70, 130) ?>" ry="<?= mt_rand(40, 85) ?>"/>
      <?php endfor; ?>
    </g>
  </svg>

  <canvas class="bg-fx" id="fx"></canvas>
  <div class="bg-veil"></div>
</div>
