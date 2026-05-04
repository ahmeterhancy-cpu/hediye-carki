<?php

namespace App\Models;

use App\Core\Database;

class SpinCode
{
    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare("SELECT * FROM spin_codes WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function activeCount(): int
    {
        return (int)Database::pdo()
            ->query("SELECT COUNT(*) FROM spin_codes WHERE status='pending' AND expires_at > NOW()")
            ->fetchColumn();
    }
}
