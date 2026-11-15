<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Auth;
use App\Core\Database;

/**
 * Entrenaments i esdeveniments especials (taula events) i vista de calendari
 * que també hi inclou els partits.
 */
final class EventRepository
{
    public const MAX_REPEAT = 60;

    /**
     * Tots els elements del calendari entre dues dates.
     * @param array<int,int> $teamIds
     * @return array<int,array<string,mixed>>
     */
    public static function calendarItems(array $teamIds, string $from, string $to): array
    {
        if (!$teamIds) {
            return [];
        }
        $in = Database::in($teamIds);
        return Database::all(
            "(SELECT e.id, e.type AS kind, e.title, e.starts_at, e.ends_at, e.location, e.team_id,
                     t.name AS team_name, t.color,
                     (SELECT COUNT(*) FROM attendance a WHERE a.event_id = e.id) AS attendance_count
              FROM events e JOIN teams t ON t.id = e.team_id
              WHERE e.team_id IN ($in) AND e.starts_at >= ? AND e.starts_at < ?)
             UNION ALL
             (SELECT m.id, 'partit' AS kind,
                     CONCAT(IF(m.is_home, 'vs ', '@ '), m.opponent) AS title,
                     m.starts_at, NULL AS ends_at, m.location, m.team_id,
                     t.name AS team_name, t.color, 0 AS attendance_count
              FROM matches m JOIN teams t ON t.id = m.team_id
              WHERE m.team_id IN ($in) AND m.starts_at >= ? AND m.starts_at < ? AND m.status <> 'cancel·lat')
             ORDER BY starts_at, team_name",
            array_merge($teamIds, [$from, $to], $teamIds, [$from, $to])
        );
    }

    /**
     * @return array<string,mixed>|null
     */
    public static function find(int $id): ?array
    {
        return Database::first(
            'SELECT e.*, t.name AS team_name, t.color, u.name AS created_by_name
             FROM events e JOIN teams t ON t.id = e.team_id
             LEFT JOIN users u ON u.id = e.created_by
             WHERE e.id = ?',
            [$id]
        );
    }

    /**
     * Entrenaments d'uns equips en un interval (per a la pantalla d'assistència).
     * @param array<int,int> $teamIds
     * @return array<int,array<string,mixed>>
     */
    public static function trainings(array $teamIds, string $from, string $to): array
    {
        if (!$teamIds) {
            return [];
        }
        return Database::all(
            "SELECT e.id, e.title, e.starts_at, e.ends_at, e.location, e.team_id, t.name AS team_name, t.color,
                    (SELECT COUNT(*) FROM attendance a WHERE a.event_id = e.id) AS marked,
                    (SELECT COUNT(*) FROM attendance a WHERE a.event_id = e.id AND a.status IN ('present','retard')) AS attended,
                    (SELECT COUNT(*) FROM team_players tp JOIN players p ON p.id = tp.player_id AND p.active = 1
                       WHERE tp.team_id = e.team_id AND tp.left_at IS NULL) AS squad
             FROM events e JOIN teams t ON t.id = e.team_id
             WHERE e.type = 'entrenament' AND e.team_id IN (" . Database::in($teamIds) . ") AND e.starts_at >= ? AND e.starts_at < ?
             ORDER BY e.starts_at DESC",
            array_merge($teamIds, [$from, $to])
        );
    }

    /**
     * Crea l'esdeveniment (i les repeticions setmanals si n'hi ha). Retorna l'id del primer.
     * @param array<string,mixed> $d
     */
    public static function create(array $d, ?string $repeatUntil): int
    {
        $start = new \DateTime($d['starts_at']);
        $end = $d['ends_at'] ? new \DateTime($d['ends_at']) : null;
        $limit = $repeatUntil ? new \DateTime($repeatUntil . ' 23:59:59') : clone $start;
        $first = 0;
        $n = 0;
        while ($start <= $limit && $n < self::MAX_REPEAT) {
            Database::query(
                'INSERT INTO events (team_id, type, title, starts_at, ends_at, location, notes, created_by, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [$d['team_id'], $d['type'], $d['title'], $start->format('Y-m-d H:i:s'), $end ? $end->format('Y-m-d H:i:s') : null,
                 $d['location'] ?: null, $d['notes'] ?: null, Auth::id() ?: null, date('Y-m-d H:i:s')]
            );
            if ($first === 0) {
                $first = (int)Database::pdo()->lastInsertId();
            }
            $start->modify('+7 days');
            if ($end) {
                $end->modify('+7 days');
            }
            $n++;
        }
        return $first;
    }

    /**
     * @param array<string,mixed> $d
     */
    public static function update(int $id, array $d): void
    {
        Database::query(
            'UPDATE events SET team_id = ?, type = ?, title = ?, starts_at = ?, ends_at = ?, location = ?, notes = ? WHERE id = ?',
            [$d['team_id'], $d['type'], $d['title'], $d['starts_at'], $d['ends_at'] ?: null, $d['location'] ?: null, $d['notes'] ?: null, $id]
        );
    }

    public static function delete(int $id): void
    {
        Database::query('DELETE FROM events WHERE id = ?', [$id]);
    }

    /**
     * Valida el formulari. Retorna [dades, repetir fins (Y-m-d|null), errors].
     * @param array<string,mixed> $in
     * @param array<int,int> $allowedTeams
     * @return array{0:array<string,mixed>,1:?string,2:array<int,string>}
     */
    public static function validate(array $in, array $allowedTeams, bool $allowRepeat): array
    {
        $errors = [];
        $type = (string)($in['type'] ?? 'entrenament') === 'especial' ? 'especial' : 'entrenament';
        $teamId = (int)($in['team_id'] ?? 0);
        $date = (string)($in['date'] ?? '');
        $startsAt = parse_datetime($date, (string)($in['start_time'] ?? ''));
        $endTime = trim((string)($in['end_time'] ?? ''));
        $endsAt = $endTime !== '' ? parse_datetime($date, $endTime) : null;

        $d = [
            'team_id'   => $teamId,
            'type'      => $type,
            'title'     => trim((string)($in['title'] ?? '')),
            'starts_at' => $startsAt,
            'ends_at'   => $endsAt,
            'location'  => mb_substr(trim((string)($in['location'] ?? '')), 0, 150),
            'notes'     => mb_substr(trim((string)($in['notes'] ?? '')), 0, 2000),
        ];

        if (!in_array($teamId, $allowedTeams, true)) {
            $errors[] = 'Tria un equip.';
        }
        if ($d['title'] === '' && $type === 'entrenament' && in_array($teamId, $allowedTeams, true)) {
            $name = (string)Database::value('SELECT name FROM teams WHERE id = ?', [$teamId]);
            $d['title'] = 'Entrenament ' . $name;
        }
        if (mb_strlen($d['title']) < 2 || mb_strlen($d['title']) > 120) {
            $errors[] = 'Escriu un títol (2-120 caràcters).';
        }
        if ($startsAt === null) {
            $errors[] = 'La data o l\'hora d\'inici no són vàlides.';
        }
        if ($endTime !== '' && $endsAt === null) {
            $errors[] = 'L\'hora de final no és vàlida.';
        } elseif ($startsAt && $endsAt && $endsAt <= $startsAt) {
            $errors[] = 'L\'hora de final ha de ser posterior a la d\'inici.';
        }

        $repeatUntil = null;
        if ($allowRepeat && !empty($in['repeat'])) {
            $repeatUntil = trim((string)($in['repeat_until'] ?? ''));
            $ru = \DateTime::createFromFormat('!Y-m-d', $repeatUntil);
            if (!$ru || $ru->format('Y-m-d') !== $repeatUntil || ($startsAt && $repeatUntil < substr($startsAt, 0, 10))) {
                $errors[] = 'La data de "repetir fins" no és vàlida.';
                $repeatUntil = null;
            } elseif ($startsAt && $ru > (new \DateTime($startsAt))->modify('+1 year')) {
                $errors[] = 'Com a màxim es pot repetir durant un any.';
            }
        }
        return [$d, $repeatUntil, $errors];
    }
}
