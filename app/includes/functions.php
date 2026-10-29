<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) {
    session_start(['cookie_httponly'=>true,'cookie_samesite'=>'Lax','use_strict_mode'=>true]);
}
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/ecosystem.php';

function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}

/** Ikon SVG inline (gaya feather) */
function icon(string $name, int $size = 20): string {
    $p = [
        'water' => '<path d="M12 2S4 11 4 16a8 8 0 0 0 16 0c0-5-8-14-8-14z"/>',
        'recycle' => '<path d="m9 3 3-1 4 7M16 4v5h-5M21 12l1 3-4 7M22 18h-5v-5M10 22H3l-2-3 4-7M2 13l4-1 2 4"/>',
        'spark' => '<path d="m12 3 2.5 6.5L21 12l-6.5 2.5L12 21l-2.5-6.5L3 12l6.5-2.5z"/>',
        'leaf'   => '<path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10z"/><path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/>',
        'cloud'  => '<path d="M18 10h-1.26A8 8 0 1 0 9 20h9a5 5 0 0 0 0-10z"/>',
        'access' => '<circle cx="12" cy="5" r="2"/><path d="M5 9l7 2 7-2M12 11v5M9 21l3-5 3 5"/>',
        'code'   => '<polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/>',
        'chart'  => '<line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/>',
        'shield' => '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><polyline points="9 12 11 14 15 10"/>',
        'arrow'  => '<line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>',
        'play'   => '<circle cx="12" cy="12" r="10"/><polygon points="10 8 16 12 10 16 10 8"/>',
        'search' => '<circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>',
        'menu'   => '<line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/>',
        'close'  => '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>',
        'tree'   => '<path d="M12 22v-6M12 16c-4 0-6-3-5-6-2-1-2-5 1-6 1-3 7-3 8 0 3 1 3 5 1 6 1 3-1 6-5 6z"/>',
        'home'   => '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/>',
        'grid'   => '<rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>',
        'folder' => '<path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>',
        'user'   => '<path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>',
        'bolt'   => '<polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>',
        'cart'   => '<circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>',
        'mail'   => '<path d="M4 4h16a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2z"/><polyline points="22,6 12,13 2,6"/>',
        'plus'   => '<line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/>',
        'minus'  => '<line x1="5" y1="12" x2="19" y2="12"/>',
        'pause'  => '<rect x="6" y="4" width="4" height="16"/><rect x="14" y="4" width="4" height="16"/>',
        'reset'  => '<polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/>',
    ];
    $inner = $p[$name] ?? $p['leaf'];
    return '<svg class="ico" width="'.$size.'" height="'.$size.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$inner.'</svg>';
}

/* ================= SITASI ================= */
/** Ubah penanda [3] atau [3,7] menjadi superscript yang menaut ke daftar referensi */
function rich(string $text): string {
    $safe = e($text);
    return preg_replace_callback('/\[(\d+(?:,\s*\d+)*)\]/', function ($m) {
        $links = [];
        foreach (preg_split('/,\s*/', $m[1]) as $n) $links[] = '<a href="#ref-' . (int)$n . '" title="Lihat referensi ' . (int)$n . '">' . (int)$n . '</a>';
        return '<sup class="cite">' . implode(',', $links) . '</sup>';
    }, $safe);
}
/** "3,6,7" → chip "Ref: 3 6 7" */
function refchips(?string $csv): string {
    if (!$csv) return '';
    $out = '';
    foreach (preg_split('/\s*,\s*/', $csv) as $n) if ($n !== '') $out .= '<a href="#ref-' . (int)$n . '">' . (int)$n . '</a>';
    return '<span class="refs" aria-label="Referensi">Ref ' . $out . '</span>';
}

