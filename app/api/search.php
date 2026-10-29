<?php
declare(strict_types=1);
require __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
$q = is_string($_GET['q'] ?? null) ? trim($_GET['q']) : '';
if (mb_strlen($q) < 2 || mb_strlen($q) > 100) { echo '[]'; exit; }
$results = [];
foreach (ecosystem_problems() as $id => $problem) {
    if (mb_stripos($problem['category'] . ' ' . $problem['title'] . ' ' . $problem['description'], $q) !== false) {
        $results[] = ['type'=>'Problem · ' . $problem['category'],'title'=>$problem['title'],'text'=>$problem['description'],'url'=>'solution.php?problem=' . $id];
    }
}
echo json_encode($results, JSON_UNESCAPED_UNICODE);
