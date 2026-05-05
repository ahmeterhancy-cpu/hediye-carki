<?php

namespace App\Models;

use App\Core\Database;

class User
{
    public static function findByEmail(string $email): ?array
    {
        $stmt = Database::pdo()->prepare("SELECT * FROM users WHERE email = :e AND role = 'admin' AND is_active = 1 LIMIT 1");
        $stmt->execute(['e' => $email]);
        return $stmt->fetch() ?: null;
    }

    public static function findById(int $id): ?array
    {
        $stmt = Database::pdo()->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function allStaff(): array
    {
        return Database::pdo()->query("
            SELECT u.*, creator.name AS created_by_name
            FROM users u
            LEFT JOIN users creator ON creator.id = u.created_by
            WHERE u.role = 'staff'
            ORDER BY u.name
        ")->fetchAll();
    }

    public static function activeStaffPinHashes(): array
    {
        return Database::pdo()->query("
            SELECT id, pin_hash FROM users
            WHERE role = 'staff' AND is_active = 1 AND pin_hash IS NOT NULL
        ")->fetchAll();
    }

    public static function createStaff(string $name, string $pinHash, int $createdBy): int
    {
        $stmt = Database::pdo()->prepare("
            INSERT INTO users (name, pin_hash, role, created_by) VALUES (:n, :ph, 'staff', :cb)
        ");
        $stmt->execute(['n' => $name, 'ph' => $pinHash, 'cb' => $createdBy]);
        return (int)Database::pdo()->lastInsertId();
    }

    public static function updatePin(int $id, string $pinHash): void
    {
        $stmt = Database::pdo()->prepare("UPDATE users SET pin_hash = :ph WHERE id = :id");
        $stmt->execute(['ph' => $pinHash, 'id' => $id]);
    }

    public static function toggle(int $id): void
    {
        $stmt = Database::pdo()->prepare("UPDATE users SET is_active = 1 - is_active WHERE id = :id AND role = 'staff'");
        $stmt->execute(['id' => $id]);
    }

    public static function delete(int $id): void
    {
        $stmt = Database::pdo()->prepare("DELETE FROM users WHERE id = :id AND role = 'staff'");
        $stmt->execute(['id' => $id]);
    }

    public static function touchLastLogin(int $id): void
    {
        $stmt = Database::pdo()->prepare("UPDATE users SET last_login_at = NOW() WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }

    public static function generateUniquePin(): string
    {
        $existing = self::activeStaffPinHashes();
        for ($i = 0; $i < 10; $i++) {
            $pin = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $collides = false;
            foreach ($existing as $row) {
                if (password_verify($pin, $row['pin_hash'])) {
                    $collides = true;
                    break;
                }
            }
            if (!$collides) return $pin;
        }
        throw new \RuntimeException('PIN_COLLISION_RETRY_FAILED');
    }

    public static function pinCollides(string $pin, ?int $excludeId = null): bool
    {
        $rows = self::activeStaffPinHashes();
        foreach ($rows as $row) {
            if ($excludeId !== null && (int)$row['id'] === $excludeId) continue;
            if (password_verify($pin, $row['pin_hash'])) return true;
        }
        return false;
    }

    // ── Admin yönetimi ────────────────────────────────────────────────

    public static function allAdmins(): array
    {
        return Database::pdo()->query("
            SELECT u.id, u.name, u.email, u.is_active, u.last_login_at, u.created_at,
                   creator.name AS created_by_name
            FROM users u
            LEFT JOIN users creator ON creator.id = u.created_by
            WHERE u.role = 'admin'
            ORDER BY u.created_at ASC
        ")->fetchAll();
    }

    public static function activeAdminCount(): int
    {
        return (int)Database::pdo()
            ->query("SELECT COUNT(*) FROM users WHERE role = 'admin' AND is_active = 1")
            ->fetchColumn();
    }

    public static function emailExists(string $email, ?int $excludeId = null): bool
    {
        $sql = "SELECT id FROM users WHERE email = :e";
        $params = ['e' => $email];
        if ($excludeId !== null) {
            $sql .= " AND id != :id";
            $params['id'] = $excludeId;
        }
        $stmt = Database::pdo()->prepare($sql);
        $stmt->execute($params);
        return (bool)$stmt->fetch();
    }

    public static function createAdmin(string $name, string $email, string $passwordHash, int $createdBy): int
    {
        $stmt = Database::pdo()->prepare("
            INSERT INTO users (name, email, password_hash, role, is_active, created_by)
            VALUES (:n, :e, :ph, 'admin', 1, :cb)
        ");
        $stmt->execute(['n' => $name, 'e' => $email, 'ph' => $passwordHash, 'cb' => $createdBy]);
        return (int)Database::pdo()->lastInsertId();
    }

    public static function updateAdmin(int $id, string $name, string $email): void
    {
        $stmt = Database::pdo()->prepare("
            UPDATE users SET name = :n, email = :e WHERE id = :id AND role = 'admin'
        ");
        $stmt->execute(['n' => $name, 'e' => $email, 'id' => $id]);
    }

    public static function updatePassword(int $id, string $passwordHash): void
    {
        $stmt = Database::pdo()->prepare("UPDATE users SET password_hash = :ph WHERE id = :id AND role = 'admin'");
        $stmt->execute(['ph' => $passwordHash, 'id' => $id]);
    }

    public static function toggleAdmin(int $id): void
    {
        $stmt = Database::pdo()->prepare("UPDATE users SET is_active = 1 - is_active WHERE id = :id AND role = 'admin'");
        $stmt->execute(['id' => $id]);
    }

    public static function deleteAdmin(int $id): void
    {
        $stmt = Database::pdo()->prepare("DELETE FROM users WHERE id = :id AND role = 'admin'");
        $stmt->execute(['id' => $id]);
    }
}
