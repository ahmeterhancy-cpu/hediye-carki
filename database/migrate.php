<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Core\Database;

$pdo = Database::pdo();

$migrations = glob(__DIR__ . '/migrations/*.sql');
sort($migrations);

echo "Migrationlar çalıştırılıyor...\n";

foreach ($migrations as $file) {
    $name = basename($file);
    echo "  → {$name} ... ";
    try {
        $sql = file_get_contents($file);
        $pdo->exec($sql);
        echo "OK\n";
    } catch (\PDOException $e) {
        echo "HATA: " . $e->getMessage() . "\n";
    }
}

echo "\nTamamlandı.\n";
