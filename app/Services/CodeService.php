<?php

namespace App\Services;

use App\Core\Database;
use App\Models\Settings;

class CodeService
{
    public function issue(int $staffId, ?string $receiptNo, ?float $amount): string
    {
        $ttl = (int)Settings::get('code_ttl_seconds', '300');

        for ($i = 0; $i < 5; $i++) {
            $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            try {
                $stmt = Database::pdo()->prepare("
                    INSERT INTO spin_codes (code, staff_id, receipt_no, receipt_amount, expires_at)
                    VALUES (:c, :s, :r, :a, DATE_ADD(NOW(), INTERVAL :ttl SECOND))
                ");
                $stmt->execute([
                    'c'   => $code,
                    's'   => $staffId,
                    'r'   => $receiptNo,
                    'a'   => $amount,
                    'ttl' => $ttl,
                ]);
                return $code;
            } catch (\PDOException $e) {
                if ($e->getCode() === '23000') continue;
                throw $e;
            }
        }
        throw new \RuntimeException('CODE_GENERATION_FAILED');
    }

    public function validate(string $code): ?array
    {
        if (!preg_match('/^\d{6}$/', $code)) return null;

        $stmt = Database::pdo()->prepare("
            SELECT * FROM spin_codes
            WHERE code = :c AND status = 'pending' AND expires_at > NOW()
            LIMIT 1
        ");
        $stmt->execute(['c' => $code]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    public function consume(int $codeId, int $participantId): void
    {
        $stmt = Database::pdo()->prepare("
            UPDATE spin_codes
            SET status = 'consumed', consumed_at = NOW(), participant_id = :pid
            WHERE id = :id AND status = 'pending'
        ");
        $stmt->execute(['id' => $codeId, 'pid' => $participantId]);
    }

    public function cancel(int $codeId, int $staffId): bool
    {
        $stmt = Database::pdo()->prepare("
            UPDATE spin_codes
            SET status = 'cancelled'
            WHERE id = :id AND staff_id = :s AND status = 'pending'
        ");
        $stmt->execute(['id' => $codeId, 's' => $staffId]);
        return $stmt->rowCount() > 0;
    }

    public function cancelBySession(int $codeId): bool
    {
        $stmt = Database::pdo()->prepare("
            UPDATE spin_codes SET status = 'cancelled'
            WHERE id = :id AND status = 'pending'
        ");
        $stmt->execute(['id' => $codeId]);
        return $stmt->rowCount() > 0;
    }

    public function expireStale(): int
    {
        return (int)Database::pdo()->exec("
            UPDATE spin_codes SET status = 'expired'
            WHERE status = 'pending' AND expires_at <= NOW()
        ");
    }
}
