#!/usr/bin/env php
<?php

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/vendor/autoload.php';

use App\Core\Database;
use App\Services\WheelEngine;

echo "=== WHEEL ENGINE STRESS TEST ===\n\n";

$pdo = Database::pdo();

// 1. Mevcut stokları yedekle
$backup = $pdo->query("SELECT prize_id, remaining_qty FROM stocks")->fetchAll();

// 2. Test stoklarını ayarla — dilim 8 stoğu 0 yap (asla seçilmemeli)
echo "Test setup: dilim #8 stoğu 0'a düşürülüyor (asla kazanılmamalı)\n";
$pdo->exec("UPDATE stocks SET remaining_qty = 0 WHERE prize_id = 8");
$pdo->exec("UPDATE stocks SET remaining_qty = 9999 WHERE prize_id != 8");

// 3. 1000 spin simulasyonu
$engine  = new WheelEngine();
$results = [];
$errors  = 0;
$start   = microtime(true);

for ($i = 1; $i <= 1000; $i++) {
    try {
        $w = $engine->pickWinner();
        $results[$w['id']] = ($results[$w['id']] ?? 0) + 1;
    } catch (\Throwable $e) {
        $errors++;
    }
}

$elapsed = microtime(true) - $start;

ksort($results);

echo "\nDağılım (1000 spin, " . number_format($elapsed, 2) . "s):\n";
$weights = $pdo->query("SELECT id, name, weight FROM prizes ORDER BY id")->fetchAll();
$totalWeight = array_sum(array_column($weights, 'weight'));

foreach ($weights as $p) {
    $count    = $results[$p['id']] ?? 0;
    $expected = round(($p['weight'] / $totalWeight) * 1000);
    $bar      = str_repeat('█', (int)($count / 10));
    printf("  #%d %-20s w=%-3d → %4d (beklenen ~%4d) %s\n",
        $p['id'], substr($p['name'], 0, 20), $p['weight'], $count, $expected, $bar);
}

// 4. Kontroller
echo "\n=== KABUL KRİTERLERİ ===\n";
$ok1 = !isset($results[8]);
echo "  [#1] Stok=0 dilim asla seçilmedi (#8): " . ($ok1 ? "✓ PASS" : "✗ FAIL ({$results[8]} kez seçildi)") . "\n";

$ok2 = $errors === 0;
echo "  [#2] 1000 spinde 0 hata: " . ($ok2 ? "✓ PASS" : "✗ FAIL ({$errors} hata)") . "\n";

$throughput = round(1000 / $elapsed);
echo "  [#3] Throughput: ~{$throughput} spin/saniye\n";

// 5. Stokları geri yükle
echo "\nStoklar geri yükleniyor...\n";
$stmt = $pdo->prepare("UPDATE stocks SET remaining_qty = :q WHERE prize_id = :id");
foreach ($backup as $row) {
    $stmt->execute(['q' => $row['remaining_qty'], 'id' => $row['prize_id']]);
}

echo "Test tamamlandı.\n";
