<?php

namespace App\Models;

use App\Core\Database;

class Settings
{
    private static ?array $cache = null;

    public static function all(): array
    {
        if (self::$cache !== null) return self::$cache;

        $rows = Database::pdo()->query("SELECT setting_key, setting_value FROM settings")->fetchAll();
        self::$cache = array_column($rows, 'setting_value', 'setting_key');
        return self::$cache;
    }

    public static function get(string $key, string $default = ''): string
    {
        return self::all()[$key] ?? $default;
    }

    public static function set(string $key, string $value): void
    {
        $stmt = Database::pdo()->prepare("
            INSERT INTO settings (setting_key, setting_value)
            VALUES (:k, :v1)
            ON DUPLICATE KEY UPDATE setting_value = :v2
        ");
        $stmt->execute(['k' => $key, 'v1' => $value, 'v2' => $value]);
        self::$cache = null;
    }

    public static function setMany(array $data): void
    {
        foreach ($data as $key => $value) {
            self::set($key, (string)$value);
        }
    }
}
