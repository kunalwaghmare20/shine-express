<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use PDO;

final class ReportService extends BaseService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /** @return array<string, mixed> */
    public function summary(?string $branchId = null): array
    {
        $branchSql = $branchId ? ' AND branch_id = :branch' : '';
        $params = $branchId ? ['branch' => $branchId] : [];

        $today = $this->scalar(
            "SELECT COUNT(*) FROM bookings WHERE DATE(scheduled_date) = CURDATE(){$branchSql}",
            $params
        );
        $pending = $this->scalar(
            "SELECT COUNT(*) FROM bookings WHERE status IN ('PENDING','CONFIRMED','ASSIGNED'){$branchSql}",
            $params
        );
        $completed = $this->scalar(
            "SELECT COUNT(*) FROM bookings WHERE status = 'COMPLETED'{$branchSql}",
            $params
        );
        $revenue = $this->scalar(
            "SELECT COALESCE(SUM(p.amount),0) FROM payments p
             JOIN bookings b ON b.id = p.booking_id
             WHERE p.status = 'COMPLETED'" . ($branchId ? ' AND b.branch_id = :branch' : ''),
            $params
        );
        $customers = $this->scalar('SELECT COUNT(*) FROM customers WHERE deleted_at IS NULL');
        $employees = $this->scalar(
            'SELECT COUNT(*) FROM employees WHERE deleted_at IS NULL' . ($branchId ? ' AND branch_id = :branch' : ''),
            $params
        );

        $statusBreakdown = $this->rows(
            "SELECT status, COUNT(*) AS total FROM bookings WHERE 1=1{$branchSql} GROUP BY status",
            $params
        );

        $popular = $this->rows(
            "SELECT s.name, COUNT(*) AS total FROM bookings b
             JOIN services s ON s.id = b.service_id
             WHERE 1=1{$branchSql}
             GROUP BY s.id, s.name ORDER BY total DESC LIMIT 8",
            $params
        );

        $monthly = $this->rows(
            "SELECT DATE_FORMAT(paid_at, '%Y-%m') AS ym, SUM(amount) AS revenue
             FROM payments p
             JOIN bookings b ON b.id = p.booking_id
             WHERE p.status = 'COMPLETED' AND p.paid_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)"
            . ($branchId ? ' AND b.branch_id = :branch' : '')
            . ' GROUP BY ym ORDER BY ym',
            $params
        );

        return compact('today', 'pending', 'completed', 'revenue', 'customers', 'employees', 'statusBreakdown', 'popular', 'monthly');
    }

    /** @param array<string, mixed> $params */
    private function scalar(string $sql, array $params = []): float|int|string
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn() ?: 0;
    }

    /**
     * @param array<string, mixed> $params
     * @return list<array<string, mixed>>
     */
    private function rows(string $sql, array $params = []): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
