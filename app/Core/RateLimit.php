<?php

namespace App\Core;

use App\Core\Database;

class RateLimit
{
    /**
     * Belirli bir anahtar için oran sınırı uygular.
     * Aşımda Response::abort(429) çağırır.
     *
     * @param string $key      Benzersiz anahtar (örn: "staff_login:1.2.3.4")
     * @param int    $max      İzin verilen maksimum deneme
     * @param int    $window   Saniye cinsinden zaman penceresi
     * @param int    $delay    Aşımda ek gecikme saniyesi (brute-force yavaşlatma)
     */
    public static function check(string $key, int $max, int $window, int $delay = 0): void
    {
        $pdo = Database::pdo();

        $pdo->exec("DELETE FROM rate_limits WHERE expires_at < NOW()");

        $stmt = $pdo->prepare("
            INSERT INTO rate_limits (cache_key, attempts, expires_at)
            VALUES (:k, 1, DATE_ADD(NOW(), INTERVAL :w1 SECOND))
            ON DUPLICATE KEY UPDATE
                attempts   = attempts + 1,
                expires_at = IF(attempts = 0, DATE_ADD(NOW(), INTERVAL :w2 SECOND), expires_at)
        ");
        $stmt->execute(['k' => $key, 'w1' => $window, 'w2' => $window]);

        $row = $pdo->prepare("SELECT attempts FROM rate_limits WHERE cache_key = :k");
        $row->execute(['k' => $key]);
        $attempts = (int)($row->fetchColumn() ?: 0);

        if ($attempts > $max) {
            if ($delay > 0) sleep($delay);
            Response::abort(429, 'Çok fazla deneme. Lütfen bekleyin.');
        }
    }

    public static function reset(string $key): void
    {
        $stmt = Database::pdo()->prepare("DELETE FROM rate_limits WHERE cache_key = :k");
        $stmt->execute(['k' => $key]);
    }
}
