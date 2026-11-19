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
    /**
     * Condició SQL: fila d'assistència que compta com a "llista passada"
     * (no és la justificada automàtica d'una jugadora de baixa aquell dia).
     */
    public static function realRowSql(string $a = 'a', string $e = 'e'): string
    {
        return "NOT ($a.status = 'justificat' AND EXISTS (SELECT 1 FROM player_absences pa WHERE pa.player_id = $a.player_id
                AND pa.starts_on <= DATE($e.starts_at) AND (pa.ends_on IS NULL OR pa.ends_on >= DATE($e.starts_at))))";
    }

    public static function summaryForEvent(int $eventId): array
    {
        $out = ['present' => 0, 'retard' => 0, 'justificat' => 0, 'absent' => 0, 'total' => 0];
        foreach (Database::all('SELECT a.status, COUNT(*) AS n FROM attendance a JOIN events e ON e.id = a.event_id
                                WHERE a.event_id = ? AND ' . self::realRowSql() . ' GROUP BY a.status', [$eventId]) as $r) {
            $out[$r['status']] = (int)$r['n'];
            $out['total'] += (int)$r['n'];
        }
        return $out;
    }

    /**
     * Llista per passar llista:
     *  - si l'esdeveniment té llista pròpia (p. ex. gimnàs): aquelles jugadores
     *  - si és compartit per diversos equips: les jugadores d'aquests equips
     *  - si és d'un equip: la plantilla de l'equip
     * Sempre s'hi afegeix qui ja tingui assistència marcada en aquest esdeveniment.
     * @return array<int,array<string,mixed>>
     */
    public static function sheet(array $event): array
    {
        $eventId = (int)$event['id'];
        $hasList = (bool)Database::value('SELECT 1 FROM event_players WHERE event_id = ? LIMIT 1', [$eventId]);
        if ($hasList) {
            $in = 'EXISTS (SELECT 1 FROM event_players ep WHERE ep.event_id = ? AND ep.player_id = p.id)';
            $params = [$eventId];
        } elseif ($event['team_id'] === null) {
            // Entrenament compartit per diversos equips: jugadores de qualsevol d'ells
            $in = 'EXISTS (SELECT 1 FROM team_players tp2 JOIN event_teams et2 ON et2.team_id = tp2.team_id AND et2.event_id = ?
                           WHERE tp2.player_id = p.id AND tp2.left_at IS NULL)';
            $params = [$eventId];
        } else {
            $in = 'EXISTS (SELECT 1 FROM team_players tp2 WHERE tp2.player_id = p.id AND tp2.team_id = ? AND tp2.left_at IS NULL)';
            $params = [(int)$event['team_id']];
        }
        // Canvis puntuals d'aquest esdeveniment: tretes i afegides
        $in = "(($in) AND NOT EXISTS (SELECT 1 FROM event_roster_changes rc WHERE rc.event_id = ? AND rc.player_id = p.id AND rc.`change` = 'treta'))
                OR EXISTS (SELECT 1 FROM event_roster_changes rc2 WHERE rc2.event_id = ? AND rc2.player_id = p.id AND rc2.`change` = 'afegida')";
        $params = array_merge($params, [$eventId, $eventId]);
        return Database::all(
            "SELECT p.id, p.first_name, p.last_name, p.position,
                    (SELECT tp.dorsal FROM team_players tp WHERE tp.player_id = p.id AND tp.team_id = ? AND tp.left_at IS NULL LIMIT 1) AS dorsal,
                    a.status, a.observation, (NOT ($in)) AS guest
             FROM players p
             LEFT JOIN attendance a ON a.player_id = p.id AND a.event_id = ?
             WHERE (p.active = 1 AND $in) OR a.id IS NOT NULL
             ORDER BY guest, p.position = 'porter' DESC, p.last_name, p.first_name",
            array_merge([(int)($event['team_id'] ?? 0)], $params, [$eventId], $params)
        );
    }

    /** Jugadores tretes d'aquest esdeveniment concret. */
    public static function removed(int $eventId): array
    {
        return Database::all(
            "SELECT p.id, p.first_name, p.last_name FROM event_roster_changes rc JOIN players p ON p.id = rc.player_id
             WHERE rc.event_id = ? AND rc.`change` = 'treta' ORDER BY p.last_name, p.first_name",
            [$eventId]
        );
    }

    /** Afegeix o treu una jugadora d'un esdeveniment concret. */
    public static function setRosterChange(int $eventId, int $playerId, string $change): void
    {
        Database::query(
            'REPLACE INTO event_roster_changes (event_id, player_id, `change`, created_at) VALUES (?, ?, ?, NOW())',
            [$eventId, $playerId, $change === 'treta' ? 'treta' : 'afegida']
        );
        // Si la treus d'aquest entreno, també se n'esborra l'assistència (ja no hi comptava)
        if ($change === 'treta') {
            Database::query('DELETE FROM attendance WHERE event_id = ? AND player_id = ?', [$eventId, $playerId]);
        }
    }

    /**
     * Condició: assistència d'esdeveniments dels equips, o de club per a jugadores d'aquests equips.
     * @param array<int,int> $teamIds
     * @return array{0:string,1:array<int,int>}
     */
    public static function scope(array $teamIds): array
    {
        $in = Database::in($teamIds);
        return [
            "(e.team_id IN ($in) OR (e.team_id IS NULL AND a.player_id IN (SELECT tps.player_id FROM team_players tps WHERE tps.team_id IN ($in) AND tps.left_at IS NULL)))",
            array_merge($teamIds, $teamIds),
        ];
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
             WHERE " . self::scope($teamIds)[0] . " AND e.starts_at >= ? AND e.starts_at < ?
             GROUP BY p.id, p.first_name, p.last_name
             ORDER BY p.last_name, p.first_name",
            array_merge(self::scope($teamIds)[1], [$from, $to])
        );
        foreach ($rows as &$r) {
            $den = (int)$r['total'] - (int)$r['justificat'];
            $r['percent'] = $den > 0 ? (int)round(((int)$r['present'] + (int)$r['retard']) / $den * 100) : null;
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
        $rows = Database::all('SELECT id, name, color FROM teams WHERE id IN (' . Database::in($teamIds) . ') ORDER BY name', $teamIds);
        foreach ($rows as &$r) {
            [$w, $p] = self::scope([(int)$r['id']]);
            $agg = Database::first(
                "SELECT COUNT(a.id) AS total, SUM(a.status <> 'justificat') AS counted, SUM(a.status IN ('present','retard')) AS attended, COUNT(DISTINCT e.id) AS sessions
                 FROM attendance a JOIN events e ON e.id = a.event_id
                 WHERE $w AND e.starts_at >= ? AND e.starts_at < ?",
                array_merge($p, [$from, $to])
            );
            $r['total'] = (int)$agg['total'];
            $r['attended'] = (int)$agg['attended'];
            $r['counted'] = (int)$agg['counted'];
            $r['sessions'] = (int)$agg['sessions'];
        }
        unset($r);
        foreach ($rows as &$r) {
            $r['percent'] = (int)$r['counted'] > 0 ? (int)round((int)$r['attended'] / (int)$r['counted'] * 100) : null;
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
                "SELECT DATE_FORMAT(e.starts_at, '%Y-%m') AS ym, COUNT(a.id) AS total, SUM(a.status <> 'justificat') AS counted, SUM(a.status IN ('present','retard')) AS attended
                 FROM attendance a JOIN events e ON e.id = a.event_id
                 WHERE " . self::scope($teamIds)[0] . " AND e.starts_at >= ?
                 GROUP BY ym",
                array_merge(self::scope($teamIds)[1], [$start->format('Y-m-d H:i:s')])
            ) as $r) {
                $data[$r['ym']] = $r;
            }
        }
        for ($i = 0; $i < $months; $i++) {
            $ym = $start->format('Y-m');
            $total = isset($data[$ym]) ? (int)$data[$ym]['total'] : 0;
            $counted = isset($data[$ym]) ? (int)$data[$ym]['counted'] : 0;
            $out[] = [
                'month'   => $ym,
                'percent' => $counted > 0 ? (int)round((int)$data[$ym]['attended'] / $counted * 100) : null,
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
            "SELECT a.status, a.observation, e.id AS event_id, e.title, e.starts_at, COALESCE(t.name, e.title) AS team_name, COALESCE(t.color, '#64748b') AS color
             FROM attendance a JOIN events e ON e.id = a.event_id LEFT JOIN teams t ON t.id = e.team_id
             WHERE a.player_id = ? ORDER BY e.starts_at DESC LIMIT ?",
            [$playerId, $limit]
        );
    }
}
