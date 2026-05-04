<?php

namespace App\Models;

use App\Core\Database;

class Participant
{
    public static function create(array $data): int
    {
        $stmt = Database::pdo()->prepare("
            INSERT INTO participants
                (first_name, last_name, phone, receipt_no, receipt_amount,
                 prize_id, prize_name_snapshot, brand_snapshot, staff_id, ip_address, user_agent)
            VALUES
                (:fn, :ln, :phone, :rno, :ramt, :pid, :pname, :brand, :sid, :ip, :ua)
        ");
        $stmt->execute([
            'fn'    => $data['first_name'],
            'ln'    => $data['last_name'],
            'phone' => $data['phone'],
            'rno'   => $data['receipt_no'] ?? null,
            'ramt'  => $data['receipt_amount'] ?? null,
            'pid'   => $data['prize_id'],
            'pname' => $data['prize_name_snapshot'],
            'brand' => $data['brand_snapshot'] ?? null,
            'sid'   => $data['staff_id'] ?? null,
            'ip'    => $data['ip_address'] ?? null,
            'ua'    => $data['user_agent'] ?? null,
        ]);
        return (int)Database::pdo()->lastInsertId();
    }

    public static function find(int $id): ?array
    {
        $stmt = Database::pdo()->prepare("
            SELECT p.*, u.name AS staff_name
            FROM participants p
            LEFT JOIN users u ON u.id = p.staff_id
            WHERE p.id = :id LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public static function filter(array $filters, int $page = 1, int $perPage = 50): array
    {
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['date_from'])) {
            $where[] = 'p.created_at >= :df';
            $params['df'] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $where[] = 'p.created_at <= :dt';
            $params['dt'] = $filters['date_to'] . ' 23:59:59';
        }
        if (!empty($filters['prize_id'])) {
            $where[] = 'p.prize_id = :prize_id';
            $params['prize_id'] = (int)$filters['prize_id'];
        }
        if (!empty($filters['phone'])) {
            $where[] = 'p.phone LIKE :phone';
            $params['phone'] = '%' . $filters['phone'] . '%';
        }

        $sql = "
            SELECT p.*, u.name AS staff_name
            FROM participants p
            LEFT JOIN users u ON u.id = p.staff_id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY p.created_at DESC
            LIMIT :limit OFFSET :offset
        ";

        $offset = ($page - 1) * $perPage;
        $pdo = Database::pdo();
        $stmt = $pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue('limit', $perPage, \PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function countFilter(array $filters): int
    {
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['date_from'])) {
            $where[] = 'p.created_at >= :df';
            $params['df'] = $filters['date_from'] . ' 00:00:00';
        }
        if (!empty($filters['date_to'])) {
            $where[] = 'p.created_at <= :dt';
            $params['dt'] = $filters['date_to'] . ' 23:59:59';
        }
        if (!empty($filters['prize_id'])) {
            $where[] = 'p.prize_id = :prize_id';
            $params['prize_id'] = (int)$filters['prize_id'];
        }
        if (!empty($filters['phone'])) {
            $where[] = 'p.phone LIKE :phone';
            $params['phone'] = '%' . $filters['phone'] . '%';
        }

        $stmt = Database::pdo()->prepare("
            SELECT COUNT(*) FROM participants p WHERE " . implode(' AND ', $where)
        );
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public static function filterAll(array $filters): array
    {
        return self::filter($filters, 1, 99999);
    }

    public static function todayCount(): int
    {
        return (int)Database::pdo()
            ->query("SELECT COUNT(*) FROM participants WHERE DATE(created_at) = CURDATE()")
            ->fetchColumn();
    }

    public static function totalCount(): int
    {
        return (int)Database::pdo()->query("SELECT COUNT(*) FROM participants")->fetchColumn();
    }

    public static function hourlyToday(): array
    {
        return Database::pdo()->query("
            SELECT HOUR(created_at) AS hour, COUNT(*) AS cnt
            FROM participants
            WHERE DATE(created_at) = CURDATE()
            GROUP BY hour ORDER BY hour
        ")->fetchAll();
    }

    public static function prizeDistribution(): array
    {
        return Database::pdo()->query("
            SELECT prize_name_snapshot AS name, COUNT(*) AS cnt
            FROM participants
            GROUP BY prize_name_snapshot ORDER BY cnt DESC
        ")->fetchAll();
    }
}
