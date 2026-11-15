<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Auth;
use App\Core\Database;

final class AttendanceRepository
{
    public const STATUSES = ['present' => 'Present', 'retard' => 'Retard', 'justificat' => 'Justificada', 'absent' => 'Absent'];

    /**
     * @return array{present:int,retard:int,justificat:int,absent:int,total:int}
     */
    public static function summaryForEvent(int $eventId): array
    {
        $out = ['present' => 0, 'retard' => 0, 'justificat' => 0, 'absent' => 0, 'total' => 0];
        foreach (Database::all('SELECT status, COUNT(*) AS n FROM attendance WHERE event_id = ? GROUP BY status', [$eventId]) as $r) {
            $out[$r['status']] = (int)$r['n'];
            $out['total'] += (int)$r['n'];
        }
        return $out;
    }

    /**
     * Plantilla de l'equip amb el que s'hagi marcat en aquest entrenament.
     * També hi surten jugadors/es d'altres equips que ja tinguin assistència
     * marcada en aquest entrenament (per exemple, si van pujar a entrenar).
     * @return array<int,array<string,mixed>>
     */
    public static function sheet(int $eventId, int $teamId): array
    {
        return Database::all(
            "SELECT p.id, p.first_name, p.last_name, p.position, tp.dorsal,
                    a.status, a.observation, (tp.id IS NULL) AS guest
             FROM players p
             LEFT JOIN team_players tp ON tp.player_id = p.id AND tp.team_id = ? AND tp.left_at IS NULL
             LEFT JOIN attendance a ON a.player_id = p.id AND a.event_id = ?
             WHERE p.active = 1 AND (tp.id IS NOT NULL OR a.id IS NOT NULL)
             ORDER BY guest, p.position = 'porter' DESC, tp.dorsal IS NULL, tp.dorsal, p.last_name, p.first_name",
            [$teamId, $eventId]
        );
    }

    /**
     * Desa la llista. $rows = [player_id => ['status' => ..., 'observation' => ...]].
     * Els jugadors/es sense estat s'esborren de la llista.
     * @param array<int,array{status:string,observation:string}> $rows
     */
    public static function save(int $eventId, array $rows): int
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            $saved = 0;
            $now = date('Y-m-d H:i:s');
            foreach ($rows as $playerId => $row) {
                if ($row['status'] === '') {
                    Database::query('DELETE FROM attendance WHERE event_id = ? AND player_id = ?', [$eventId, $playerId]);
                    continue;
                }
                Database::query(
                    'INSERT INTO attendance (event_id, player_id, status, observation, recorded_by, recorded_at)
                     VALUES (?, ?, ?, ?, ?, ?)
                     ON DUPLICATE KEY UPDATE status = VALUES(status), observation = VALUES(observation),
                                             recorded_by = VALUES(recorded_by), recorded_at = VALUES(recorded_at)',
                    [$eventId, $playerId, $row['status'], $row['observation'] !== '' ? $row['observation'] : null, Auth::id() ?: null, $now]
                );
                $saved++;
            }
            $pdo->commit();
            return $saved;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Estadístiques per jugador/a en un interval.
     * @param array<int,int> $teamIds
     * @return array<int,array<string,mixed>>
     */
    public static function byPlayer(array $teamIds, string $from, string $to): array
    {
        if (!$teamIds) {
            return [];
        }
        $rows = Database::all(
            "SELECT p.id, p.first_name, p.last_name,
                    COUNT(a.id) AS total,
                    SUM(a.status = 'present') AS present,
                    SUM(a.status = 'retard') AS retard,
                    SUM(a.status = 'justificat') AS justificat,
                    SUM(a.status = 'absent') AS absent
             FROM attendance a
             JOIN events e ON e.id = a.event_id
             JOIN players p ON p.id = a.player_id AND p.active = 1
             WHERE e.team_id IN (" . Database::in($teamIds) . ") AND e.starts_at >= ? AND e.starts_at < ?
             GROUP BY p.id, p.first_name, p.last_name
             ORDER BY p.last_name, p.first_name",
            array_merge($teamIds, [$from, $to])
        );
        foreach ($rows as &$r) {
            $r['percent'] = (int)$r['total'] > 0 ? (int)round(((int)$r['present'] + (int)$r['retard']) / (int)$r['total'] * 100) : null;
        }
        return $rows;
    }

    /**
     * Percentatge per equip en un interval.
     * @param array<int,int> $teamIds
     * @return array<int,array<string,mixed>>
     */
    public static function byTeam(array $teamIds, string $from, string $to): array
    {
        if (!$teamIds) {
            return [];
        }
        $rows = Database::all(
            "SELECT t.id, t.name, t.color, COUNT(a.id) AS total,
                    SUM(a.status IN ('present','retard')) AS attended,
                    COUNT(DISTINCT e.id) AS sessions
             FROM teams t
             LEFT JOIN events e ON e.team_id = t.id AND e.type = 'entrenament' AND e.starts_at >= ? AND e.starts_at < ?
             LEFT JOIN attendance a ON a.event_id = e.id
             WHERE t.id IN (" . Database::in($teamIds) . ")
             GROUP BY t.id, t.name, t.color ORDER BY t.name",
            array_merge([$from, $to], $teamIds)
        );
        foreach ($rows as &$r) {
            $r['percent'] = (int)$r['total'] > 0 ? (int)round((int)$r['attended'] / (int)$r['total'] * 100) : null;
        }
        return $rows;
    }

    /**
     * Percentatge mensual dels últims $months mesos.
     * @param array<int,int> $teamIds
     * @return array<int,array{month:string,percent:int|null,total:int}>
     */
    public static function monthly(array $teamIds, int $months = 6): array
    {
        $out = [];
        $start = (new \DateTime('first day of this month'))->modify('-' . ($months - 1) . ' months')->setTime(0, 0);
        $data = [];
        if ($teamIds) {
            foreach (Database::all(
                "SELECT DATE_FORMAT(e.starts_at, '%Y-%m') AS ym, COUNT(a.id) AS total, SUM(a.status IN ('present','retard')) AS attended
                 FROM attendance a JOIN events e ON e.id = a.event_id
                 WHERE e.team_id IN (" . Database::in($teamIds) . ") AND e.starts_at >= ?
                 GROUP BY ym",
                array_merge($teamIds, [$start->format('Y-m-d H:i:s')])
            ) as $r) {
                $data[$r['ym']] = $r;
            }
        }
        for ($i = 0; $i < $months; $i++) {
            $ym = $start->format('Y-m');
            $total = isset($data[$ym]) ? (int)$data[$ym]['total'] : 0;
            $out[] = [
                'month'   => $ym,
                'percent' => $total > 0 ? (int)round((int)$data[$ym]['attended'] / $total * 100) : null,
                'total'   => $total,
            ];
            $start->modify('+1 month');
        }
        return $out;
    }

    /**
     * Historial d'un jugador/a (últimes sessions).
     * @return array<int,array<string,mixed>>
     */
    public static function playerHistory(int $playerId, int $limit = 12): array
    {
        return Database::all(
            'SELECT a.status, a.observation, e.id AS event_id, e.title, e.starts_at, t.name AS team_name, t.color
             FROM attendance a JOIN events e ON e.id = a.event_id JOIN teams t ON t.id = e.team_id
             WHERE a.player_id = ? ORDER BY e.starts_at DESC LIMIT ?',
            [$playerId, $limit]
        );
    }
}
