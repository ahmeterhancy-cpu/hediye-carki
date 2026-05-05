<?php

namespace App\Models;

use App\Core\Database;

class Stock
{
    public static function upsert(int $prizeId, int $initialQty, int $remainingQty, ?int $dailyLimit = null): void
    {
        // Placeholder isimleri INSERT ve UPDATE bölümlerinde tekrar edilemez
        // (emulate_prepares=false). Her bölüm için ayrı isim:
        $stmt = Database::pdo()->prepare("
            INSERT INTO stocks (prize_id, initial_qty, remaining_qty, daily_limit)
            VALUES (:pid, :iq1, :rq1, :dl1)
            ON DUPLICATE KEY UPDATE
                initial_qty   = :iq2,
                remaining_qty = :rq2,
                daily_limit   = :dl2
        ");
        $stmt->execute([
            'pid' => $prizeId,
            'iq1' => $initialQty,  'iq2' => $initialQty,
            'rq1' => $remainingQty,'rq2' => $remainingQty,
            'dl1' => $dailyLimit,  'dl2' => $dailyLimit,
        ]);
    }

    public static function resetAllToInitial(): int
    {
        return (int)Database::pdo()->exec("UPDATE stocks SET remaining_qty = initial_qty");
    }

    public static function status(): array
    {
        return Database::pdo()->query("
            SELECT s.prize_id, p.name, p.brand_name, s.remaining_qty, s.initial_qty, s.daily_limit
            FROM stocks s
            JOIN prizes p ON p.id = s.prize_id
            ORDER BY p.display_order, p.id
        ")->fetchAll();
    }
}
