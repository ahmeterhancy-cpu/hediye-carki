<?php

namespace App\Models;

use App\Core\Database;

class Prize
{
    public static function allActive(): array
    {
        return Database::pdo()->query("
            SELECT p.*, s.remaining_qty, s.initial_qty, s.daily_limit
            FROM prizes p
            LEFT JOIN stocks s ON s.prize_id = p.id
            WHERE p.is_active = 1
            ORDER BY p.display_order, p.id
        ")->fetchAll();
    }

    public static function all(): array
    {
        return Database::pdo()->query("
            SELECT p.*, s.remaining_qty, s.initial_qty, s.daily_limit
            FROM prizes p
            LEFT JOIN stocks s ON s.prize_id = p.id
            ORDER BY p.display_order, p.id
        ")->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare("
            SELECT p.*, s.remaining_qty, s.initial_qty, s.daily_limit
            FROM prizes p
            LEFT JOIN stocks s ON s.prize_id = p.id
            WHERE p.id = :id LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function create(array $data): int
    {
        $stmt = Database::pdo()->prepare("
            INSERT INTO prizes (name, brand_name, pickup_location, logo_path, color_hex, weight, display_order, is_active)
            VALUES (:name, :brand, :pickup, :logo, :color, :weight, :order, :active)
        ");
        $stmt->execute([
            'name'   => $data['name'],
            'brand'  => $data['brand_name'] ?? null,
            'pickup' => $data['pickup_location'] ?? null,
            'logo'   => $data['logo_path'] ?? null,
            'color'  => $data['color_hex'] ?? '#FFB400',
            'weight' => (int)($data['weight'] ?? 10),
            'order'  => (int)($data['display_order'] ?? 0),
            'active' => isset($data['is_active']) ? (int)$data['is_active'] : 1,
        ]);
        return (int)Database::pdo()->lastInsertId();
    }

    public static function update(int $id, array $data): void
    {
        $stmt = Database::pdo()->prepare("
            UPDATE prizes SET
                name            = :name,
                brand_name      = :brand,
                pickup_location = :pickup,
                logo_path       = :logo,
                color_hex       = :color,
                weight          = :weight,
                display_order   = :order,
                is_active       = :active
            WHERE id = :id
        ");
        $stmt->execute([
            'name'   => $data['name'],
            'brand'  => $data['brand_name'] ?? null,
            'pickup' => $data['pickup_location'] ?? null,
            'logo'   => $data['logo_path'] ?? null,
            'color'  => $data['color_hex'] ?? '#FFB400',
            'weight' => (int)($data['weight'] ?? 10),
            'order'  => (int)($data['display_order'] ?? 0),
            'active' => isset($data['is_active']) ? (int)$data['is_active'] : 1,
            'id'     => $id,
        ]);
    }

    public static function delete(int $id): void
    {
        $stmt = Database::pdo()->prepare("DELETE FROM prizes WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }

    public static function participantCount(int $id): int
    {
        $stmt = Database::pdo()->prepare("SELECT COUNT(*) FROM participants WHERE prize_id = :id");
        $stmt->execute(['id' => $id]);
        return (int)$stmt->fetchColumn();
    }

    public static function deactivate(int $id): void
    {
        $stmt = Database::pdo()->prepare("UPDATE prizes SET is_active = 0 WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }

    public static function reorder(array $orderedIds): void
    {
        $pdo = Database::pdo();
        $stmt = $pdo->prepare("UPDATE prizes SET display_order = :ord WHERE id = :id");
        foreach ($orderedIds as $order => $id) {
            $stmt->execute(['ord' => $order, 'id' => (int)$id]);
        }
    }
}
