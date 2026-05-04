<?php

namespace App\Services;

use App\Core\Database;

class WheelEngine
{
    public function pickWinner(): array
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();

        try {
            $stmt = $pdo->query("
                SELECT p.id, p.name, p.weight, p.logo_path, p.brand_name, p.color_hex,
                       p.display_order, s.remaining_qty
                FROM prizes p
                INNER JOIN stocks s ON s.prize_id = p.id
                WHERE p.is_active = 1 AND s.remaining_qty > 0
                FOR UPDATE
            ");
            $eligible = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            if (empty($eligible)) {
                $pdo->rollBack();
                throw new \RuntimeException('NO_STOCK_AVAILABLE');
            }

            $totalWeight = array_sum(array_column($eligible, 'weight'));
            $rand        = random_int(1, $totalWeight);
            $cumulative  = 0;
            $winner      = null;

            foreach ($eligible as $prize) {
                $cumulative += (int)$prize['weight'];
                if ($rand <= $cumulative) {
                    $winner = $prize;
                    break;
                }
            }

            $upd = $pdo->prepare("
                UPDATE stocks
                SET remaining_qty = remaining_qty - 1
                WHERE prize_id = :pid AND remaining_qty > 0
            ");
            $upd->execute(['pid' => $winner['id']]);

            if ($upd->rowCount() === 0) {
                $pdo->rollBack();
                throw new \RuntimeException('STOCK_RACE_CONDITION');
            }

            $pdo->commit();
            return $winner;

        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }

    public function calculateTargetAngle(int $winnerId, array $allActivePrizes): float
    {
        $count      = count($allActivePrizes);
        $sliceAngle = 360.0 / $count;

        foreach ($allActivePrizes as $idx => $prize) {
            if ((int)$prize['id'] === $winnerId) {
                $center = $idx * $sliceAngle + ($sliceAngle / 2);
                return 360.0 * 5 + (360.0 - $center);
            }
        }
        return 0.0;
    }
}
