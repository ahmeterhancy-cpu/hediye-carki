<?php

namespace App\Services;

use App\Core\Database;
use App\Models\Settings;

class EligibilityChecker
{
    public function canParticipate(string $phone): array
    {
        $settings = Settings::all();

        if (($settings['event_active'] ?? '0') !== '1') {
            return ['ok' => false, 'reason' => 'EVENT_INACTIVE', 'message' => 'Etkinlik şu anda aktif değil.'];
        }

        $now = date('H:i');
        $start = $settings['start_time'] ?? '00:00';
        $end   = $settings['end_time']   ?? '23:59';

        if ($now < $start || $now > $end) {
            return [
                'ok'      => false,
                'reason'  => 'OUT_OF_HOURS',
                'message' => "Etkinlik saatleri: {$start} – {$end}",
            ];
        }

        $window = (int)($settings['per_phone_window'] ?? 7);
        $limit  = (int)($settings['per_phone_limit']  ?? 1);

        $stmt = Database::pdo()->prepare("
            SELECT COUNT(*) FROM participants
            WHERE phone = :phone AND created_at >= DATE_SUB(NOW(), INTERVAL :win DAY)
        ");
        $stmt->execute(['phone' => $phone, 'win' => $window]);

        if ((int)$stmt->fetchColumn() >= $limit) {
            return [
                'ok'      => false,
                'reason'  => 'PHONE_LIMIT_REACHED',
                'message' => "Bu telefon numarası son {$window} günde {$limit} kez katılım hakkını kullanmış.",
            ];
        }

        return ['ok' => true];
    }

    public function eventIsOpen(): array
    {
        $settings = Settings::all();

        if (($settings['event_active'] ?? '0') !== '1') {
            return ['ok' => false, 'reason' => 'EVENT_INACTIVE'];
        }

        $now   = date('H:i');
        $start = $settings['start_time'] ?? '00:00';
        $end   = $settings['end_time']   ?? '23:59';

        if ($now < $start || $now > $end) {
            return ['ok' => false, 'reason' => 'OUT_OF_HOURS'];
        }

        return ['ok' => true];
    }
}
