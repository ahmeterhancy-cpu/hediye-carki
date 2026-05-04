<?php

namespace App\Models;

use App\Core\Database;

class Stock
{
    public static function upsert(int $prizeId, int $initialQty, int $remainingQty, ?int $dailyLimit = null): void
    {
        $stmt = Database::pdo()->prepare("
            INSERT INTO stocks (prize_id, initial_qty, remaining_qty, daily_limit)
            VALUES (:pid, :iq, :rq, :dl)
            ON DUPLICATE KEY UPDATE
                initial_qty   = :iq,
                remaining_qty = :rq,
                daily_limit   = :dl
        ");
        $stmt->execute(['pid' => $prizeId, 'iq' => $initialQty, 'rq' => $remainingQty, 'dl' => $dailyLimit]);
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
