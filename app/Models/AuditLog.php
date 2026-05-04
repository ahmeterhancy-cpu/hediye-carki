<?php

namespace App\Models;

use App\Core\Database;

class AuditLog
{
    public static function write(
        ?int $userId,
        string $action,
        ?string $entityType,
        ?int $entityId,
        array $payload,
        string $ip = ''
    ): void {
        // PIN değerlerinin loglara sızmaması için payload'dan temizle
        unset($payload['pin'], $payload['password'], $payload['pin_hash']);

        $stmt = Database::pdo()->prepare("
            INSERT INTO audit_logs (user_id, action, entity_type, entity_id, payload, ip_address)
            VALUES (:uid, :action, :etype, :eid, :payload, :ip)
        ");
        $stmt->execute([
            'uid'     => $userId,
            'action'  => $action,
            'etype'   => $entityType,
            'eid'     => $entityId,
            'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'ip'      => $ip,
        ]);
    }
}
