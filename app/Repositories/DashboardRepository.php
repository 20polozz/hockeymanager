<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

/**
 * Consultes del dashboard, sempre limitades als equips indicats
 * (els que l'usuari pot veure o el seleccionat a la barra superior).
 */
final class DashboardRepository
{
    /** @var array<int,int> */
    private $teamIds;
    /** @var string */
    private $now;
    /** @var string */
    private $in;

    /**
     * @param array<int,int> $teamIds
     */
    public function __construct(array $teamIds)
    {
        $this->teamIds = array_values($teamIds);
        $this->now = date('Y-m-d H:i:s');
        $this->in = Database::in($this->teamIds);
    }

    public function isEmpty(): bool
    {
        return !$this->teamIds;
    }

    public function totalPlayers(): int
    {
        if ($this->isEmpty()) {
            return 0;
        }
        return (int)Database::value(
            "SELECT COUNT(DISTINCT tp.player_id) FROM team_players tp
             JOIN players p ON p.id = tp.player_id AND p.active = 1
             WHERE tp.left_at IS NULL AND tp.team_id IN ($this->in)",
            $this->teamIds
        );
    }

    /**
     * Assistència dels últims $days dies: presents + retards sobre el total registrat.
     * @return array{percent:int|null,sessions:int}
     */
    public function attendanceRate(int $days = 30): array
    {
        if ($this->isEmpty()) {
            return ['percent' => null, 'sessions' => 0];
        }
        [$w, $p] = AttendanceRepository::scope($this->teamIds);
        $row = Database::first(
            "SELECT COUNT(*) AS total,
                    SUM(a.status IN ('present','retard')) AS attended,
                    COUNT(DISTINCT a.event_id) AS sessions
             FROM attendance a JOIN events e ON e.id = a.event_id
             WHERE $w AND e.starts_at BETWEEN ? AND ?",
            array_merge($p, [date('Y-m-d H:i:s', time() - $days * 86400), $this->now])
        );
        $total = (int)($row['total'] ?? 0);
        return [
            'percent'  => $total > 0 ? (int)round(((int)$row['attended'] / $total) * 100) : null,
            'sessions' => (int)($row['sessions'] ?? 0),
        ];
    }

    /**
     * @return array<string,mixed>|null
     */
    public function nextTraining(): ?array
    {
        if ($this->isEmpty()) {
            return null;
        }
        [$vw, $vp] = EventRepository::visibility('e', $this->teamIds);
        return Database::first(
            "SELECT e.id, e.title, e.starts_at, e.ends_at, e.location, COALESCE(t.name, e.title) AS team_name, COALESCE(t.color, '#64748b') AS color
             FROM events e LEFT JOIN teams t ON t.id = e.team_id
             WHERE e.type = 'entrenament' AND $vw AND e.starts_at >= ?
             ORDER BY e.starts_at LIMIT 1",
            array_merge($vp, [$this->now])
        );
    }

    /**
     * @return array<string,mixed>|null
     */
    public function nextMatch(): ?array
    {
        if ($this->isEmpty()) {
            return null;
        }
        return Database::first(
            "SELECT m.id, m.opponent, m.competition, m.starts_at, m.location, m.is_home, t.name AS team_name, t.color
             FROM matches m JOIN teams t ON t.id = m.team_id
             WHERE m.status = 'programat' AND m.team_id IN ($this->in) AND m.starts_at >= ?
             ORDER BY m.starts_at LIMIT 1",
            array_merge($this->teamIds, [$this->now])
        );
    }

    /**
     * Pròxims esdeveniments (entrenaments, especials i partits) barrejats per data.
     * @return array<int,array<string,mixed>>
     */
    public function upcomingEvents(int $limit = 6): array
    {
        if ($this->isEmpty()) {
            return [];
        }
        [$vw, $vp] = EventRepository::visibility('e', $this->teamIds);
        $params = array_merge($vp, [$this->now], $this->teamIds, [$this->now], [$limit]);
        return Database::all(
            "(SELECT e.type AS kind, e.title, e.starts_at, e.location, " . EventRepository::labelSql() . " AS team_name, COALESCE(t.color, '#64748b') AS color
              FROM events e LEFT JOIN teams t ON t.id = e.team_id
              WHERE $vw AND e.starts_at >= ?)
             UNION ALL
             (SELECT 'partit' AS kind,
                     CONCAT(IF(m.is_home, 'vs ', '@ '), m.opponent) AS title,
                     m.starts_at, m.location, t.name AS team_name, t.color
              FROM matches m JOIN teams t ON t.id = m.team_id
              WHERE m.status = 'programat' AND m.team_id IN ($this->in) AND m.starts_at >= ?)
             ORDER BY starts_at LIMIT ?",
            $params
        );
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function lastResults(int $limit = 5): array
    {
        if ($this->isEmpty()) {
            return [];
        }
        return Database::all(
            "SELECT m.opponent, m.competition, m.starts_at, m.is_home, m.goals_for, m.goals_against,
                    t.name AS team_name, t.color
             FROM matches m JOIN teams t ON t.id = m.team_id
             WHERE m.status = 'jugat' AND m.team_id IN ($this->in)
             ORDER BY m.starts_at DESC LIMIT ?",
            array_merge($this->teamIds, [$limit])
        );
    }

    /**
     * Resum de l'última sessió amb llista passada.
     * @return array<string,mixed>|null
     */
    public function lastSession(): ?array
    {
        if ($this->isEmpty()) {
            return null;
        }
        [$vw, $vp] = EventRepository::visibility('e', $this->teamIds);
        $event = Database::first(
            "SELECT e.id, e.title, e.starts_at, COALESCE(t.name, e.title) AS team_name
             FROM events e LEFT JOIN teams t ON t.id = e.team_id
             WHERE $vw AND e.starts_at <= ?
               AND EXISTS (SELECT 1 FROM attendance a WHERE a.event_id = e.id)
             ORDER BY e.starts_at DESC LIMIT 1",
            array_merge($vp, [$this->now])
        );
        if (!$event) {
            return null;
        }
        $counts = ['present' => 0, 'absent' => 0, 'justificat' => 0, 'retard' => 0];
        foreach (Database::all('SELECT status, COUNT(*) AS n FROM attendance WHERE event_id = ? GROUP BY status', [$event['id']]) as $r) {
            $counts[$r['status']] = (int)$r['n'];
        }
        return $event + ['counts' => $counts];
    }

    /**
     * Percentatge d'assistència per equip (últims $days dies).
     * @return array<int,array<string,mixed>>
     */
    public function attendanceByTeam(int $days = 30): array
    {
        if ($this->isEmpty()) {
            return [];
        }
        $rows = AttendanceRepository::byTeam($this->teamIds, date('Y-m-d H:i:s', time() - $days * 86400), $this->now);
        foreach ($rows as &$r) {
            $r['percent'] = (int)$r['total'] > 0 ? (int)round((int)$r['attended'] / (int)$r['total'] * 100) : null;
        }
        return $rows;
    }

    /**
     * Convocatòries enviades de partits futurs amb respostes pendents.
     * @return array<int,array<string,mixed>>
     */
    public function pendingCallups(): array
    {
        if ($this->isEmpty()) {
            return [];
        }
        return Database::all(
            "SELECT c.id, m.opponent, m.starts_at, m.is_home, t.name AS team_name, t.color,
                    SUM(cp.response = 'confirmat') AS confirmed,
                    SUM(cp.response = 'rebutjat') AS declined,
                    SUM(cp.response = 'pendent') AS pending,
                    COUNT(cp.player_id) AS total
             FROM callups c
             JOIN matches m ON m.id = c.match_id
             JOIN teams t ON t.id = m.team_id
             JOIN callup_players cp ON cp.callup_id = c.id
             WHERE c.sent_at IS NOT NULL AND m.starts_at >= ? AND m.team_id IN ($this->in)
             GROUP BY c.id, m.opponent, m.starts_at, m.is_home, t.name, t.color
             HAVING pending > 0
             ORDER BY m.starts_at",
            array_merge([$this->now], $this->teamIds)
        );
    }

    /**
     * Convocatòries pendents de resposta d'un jugador concret.
     * @return array<int,array<string,mixed>>
     */
    public static function pendingCallupsForPlayer(int $playerId): array
    {
        return Database::all(
            "SELECT c.id, m.opponent, m.starts_at, m.is_home, m.location, c.meeting_at, t.name AS team_name, t.color
             FROM callup_players cp
             JOIN callups c ON c.id = cp.callup_id AND c.sent_at IS NOT NULL
             JOIN matches m ON m.id = c.match_id
             JOIN teams t ON t.id = m.team_id
             WHERE cp.player_id = ? AND cp.response = 'pendent' AND m.starts_at >= ?
             ORDER BY m.starts_at",
            [$playerId, date('Y-m-d H:i:s')]
        );
    }

    /**
     * Assistència personal d'un jugador (últims $days dies).
     */
    public static function playerAttendanceRate(int $playerId, int $days = 30): ?int
    {
        $row = Database::first(
            "SELECT COUNT(*) AS total, SUM(a.status IN ('present','retard')) AS attended
             FROM attendance a JOIN events e ON e.id = a.event_id
             WHERE a.player_id = ? AND e.starts_at >= ?",
            [$playerId, date('Y-m-d H:i:s', time() - $days * 86400)]
        );
        $total = (int)($row['total'] ?? 0);
        return $total > 0 ? (int)round(((int)$row['attended'] / $total) * 100) : null;
    }
}
