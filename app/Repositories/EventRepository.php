<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Auth;
use App\Core\Database;
use App\Core\TeamScope;

/**
 * Entrenaments, sessions de gimnàs i esdeveniments especials (taula events).
 * Un esdeveniment pot ser d'un equip (team_id) o de tot el club (team_id NULL),
 * i pot tenir una llista pròpia de jugadors/es (event_players), com els grups de gimnàs.
 */
final class EventRepository
{
    public const MAX_REPEAT = 60;
    public const TYPES = ['entrenament' => 'Entrenament', 'gimnas' => 'Gimnàs', 'especial' => 'Esdeveniment especial'];
    public const TRAINING_TYPES = ['entrenament', 'gimnas'];

    /**
     * Expressió SQL amb el nom a mostrar: l'equip, els equips compartits o "N jugadores".
     */
    public static function labelSql(string $e = 'e', string $t = 't'): string
    {
        return "COALESCE($t.name,
                    (SELECT CASE
                              WHEN COUNT(*) = 0 THEN NULL
                              WHEN COUNT(*) >= (SELECT COUNT(*) FROM teams ta JOIN seasons sa ON sa.id = ta.season_id AND sa.is_current = 1) THEN 'Tots els equips'
                              WHEN COUNT(*) > 2 THEN CONCAT(COUNT(*), ' equips')
                              ELSE GROUP_CONCAT(tl.name ORDER BY tl.name SEPARATOR ', ')
                            END
                     FROM event_teams etl JOIN teams tl ON tl.id = etl.team_id WHERE etl.event_id = $e.id),
                    (SELECT CONCAT(COUNT(*), ' jugadores') FROM event_players epl WHERE epl.event_id = $e.id))";
    }

    /**
     * Condició de visibilitat: esdeveniments d'algun dels equips indicats (propis o compartits)
     * i els que tenen una llista de jugadores.
     * Un jugador/a només veu els esdeveniments de club amb llista si hi és.
     * @param array<int,int> $teamIds
     * @return array{0:string,1:array<int,mixed>}
     */
    public static function visibility(string $alias, array $teamIds): array
    {
        $parts = [];
        $params = [];
        if ($teamIds) {
            $in = Database::in($teamIds);
            $parts[] = "$alias.team_id IN ($in)";
            $parts[] = "EXISTS (SELECT 1 FROM event_teams xt WHERE xt.event_id = $alias.id AND xt.team_id IN ($in))";
            $params = array_merge($teamIds, $teamIds);
        }
        // Esdeveniments amb llista pròpia (gimnàs): el personal els veu; una jugadora, només si hi és
        $list = "$alias.team_id IS NULL AND EXISTS (SELECT 1 FROM event_players x WHERE x.event_id = $alias.id)";
        if (Auth::hasRole(Auth::ROLE_PLAYER)) {
            $user = Auth::user();
            $list = "$alias.team_id IS NULL AND EXISTS (SELECT 1 FROM event_players x WHERE x.event_id = $alias.id AND x.player_id = ?)";
            $params[] = (int)($user['player_id'] ?? 0);
        } elseif (!$teamIds) {
            $list = '1 = 0';
        }
        $parts[] = "($list)";
        return ['(' . implode(' OR ', $parts) . ')', $params];
    }

    public static function canAccess(array $event): bool
    {
        if ($event['team_id'] !== null) {
            return TeamScope::canAccess((int)$event['team_id']);
        }
        [$w, $p] = self::visibility('e', TeamScope::ids());
        return (bool)Database::value("SELECT 1 FROM events e WHERE e.id = ? AND $w", array_merge([$event['id']], $p));
    }

    /**
     * Tots els elements del calendari entre dues dates (inclou els partits).
     * @param array<int,int> $teamIds
     * @return array<int,array<string,mixed>>
     */
    public static function calendarItems(array $teamIds, string $from, string $to, int $onlyTeam = 0): array
    {
        [$vw, $vp] = self::visibility('e', $teamIds);
        if ($onlyTeam > 0) {
            // Amb un equip triat: els seus, els compartits amb ell i els gimnasos amb alguna jugadora seva
            $vw .= " AND (e.team_id = ? OR EXISTS (SELECT 1 FROM event_teams y WHERE y.event_id = e.id AND y.team_id = ?)
                          OR EXISTS (SELECT 1 FROM event_players y JOIN team_players tp ON tp.player_id = y.player_id AND tp.team_id = ? AND tp.left_at IS NULL WHERE y.event_id = e.id))";
            array_push($vp, $onlyTeam, $onlyTeam, $onlyTeam);
        }
        $events = Database::all(
            "SELECT e.id, e.type AS kind, e.title, e.starts_at, e.ends_at, e.location, e.team_id,
                    " . self::labelSql() . " AS team_name, COALESCE(t.color, '#64748b') AS color,
                    (SELECT COUNT(*) FROM attendance a WHERE a.event_id = e.id) AS attendance_count,
                    (SELECT COUNT(*) FROM event_players ep WHERE ep.event_id = e.id) AS participants
             FROM events e LEFT JOIN teams t ON t.id = e.team_id
             WHERE $vw AND e.starts_at >= ? AND e.starts_at < ?",
            array_merge($vp, [$from, $to])
        );
        $matches = $teamIds ? Database::all(
            "SELECT m.id, 'partit' AS kind, CONCAT(IF(m.is_home, 'vs ', '@ '), m.opponent) AS title,
                    m.starts_at, NULL AS ends_at, m.location, m.team_id, t.name AS team_name, t.color,
                    0 AS attendance_count, 0 AS participants
             FROM matches m JOIN teams t ON t.id = m.team_id
             WHERE m.team_id IN (" . Database::in($teamIds) . ") AND m.starts_at >= ? AND m.starts_at < ? AND m.status <> 'cancel·lat'",
            array_merge($teamIds, [$from, $to])
        ) : [];
        $items = array_merge($events, $matches);
        usort($items, function (array $a, array $b): int {
            return [$a['starts_at'], $a['team_name']] <=> [$b['starts_at'], $b['team_name']];
        });
        return $items;
    }

    /**
     * @return array<string,mixed>|null
     */
    public static function find(int $id): ?array
    {
        $e = Database::first(
            "SELECT e.*, " . self::labelSql() . " AS team_name, COALESCE(t.color, '#64748b') AS color, u.name AS created_by_name,
                    (SELECT COUNT(*) FROM events s WHERE e.series_id IS NOT NULL AND s.series_id = e.series_id AND s.starts_at > e.starts_at) AS later_in_series
             FROM events e LEFT JOIN teams t ON t.id = e.team_id
             LEFT JOIN users u ON u.id = e.created_by
             WHERE e.id = ?",
            [$id]
        );
        if ($e) {
            $e['teams'] = Database::all('SELECT t.id, t.name, t.color FROM event_teams et JOIN teams t ON t.id = et.team_id WHERE et.event_id = ? ORDER BY t.name', [$id]);
            $e['participants'] = Database::all(
                'SELECT p.id, p.first_name, p.last_name, p.position FROM event_players ep JOIN players p ON p.id = ep.player_id
                 WHERE ep.event_id = ? ORDER BY p.last_name, p.first_name',
                [$id]
            );
        }
        return $e;
    }

    /**
     * Entrenaments i gimnàs d'un interval (pantalla d'assistència).
     * @param array<int,int> $teamIds
     * @return array<int,array<string,mixed>>
     */
    public static function trainings(array $teamIds, string $from, string $to, bool $includeClub = true): array
    {
        [$vw, $vp] = $includeClub ? self::visibility('e', $teamIds) : ['e.team_id IN (' . Database::in($teamIds) . ')', $teamIds];
        if (!$teamIds && !$includeClub) {
            return [];
        }
        return Database::all(
            "SELECT e.id, e.type, e.title, e.starts_at, e.ends_at, e.location, e.team_id,
                    " . self::labelSql() . " AS team_name, COALESCE(t.color, '#64748b') AS color,
                    (SELECT COUNT(*) FROM attendance a WHERE a.event_id = e.id) AS marked,
                    (SELECT COUNT(*) FROM attendance a WHERE a.event_id = e.id AND a.status IN ('present','retard')) AS attended
             FROM events e LEFT JOIN teams t ON t.id = e.team_id
             WHERE e.type IN ('entrenament','gimnas') AND $vw AND e.starts_at >= ? AND e.starts_at < ?
             ORDER BY e.starts_at DESC",
            array_merge($vp, [$from, $to])
        );
    }

    /**
     * Crea l'esdeveniment (i les repeticions setmanals). Retorna l'id del primer.
     * @param array<string,mixed> $d
     * @param array<int,int> $participants
     */
    public static function create(array $d, ?string $repeatUntil, array $participants = [], array $teams = []): int
    {
        $start = new \DateTime($d['starts_at']);
        $end = $d['ends_at'] ? new \DateTime($d['ends_at']) : null;
        $limit = $repeatUntil ? new \DateTime($repeatUntil . ' 23:59:59') : clone $start;
        $series = $repeatUntil ? bin2hex(random_bytes(8)) : null;
        $first = 0;
        $n = 0;
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            while ($start <= $limit && $n < self::MAX_REPEAT) {
                Database::query(
                    'INSERT INTO events (team_id, type, title, starts_at, ends_at, location, notes, series_id, created_by, created_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    [$d['team_id'] ?: null, $d['type'], $d['title'], $start->format('Y-m-d H:i:s'), $end ? $end->format('Y-m-d H:i:s') : null,
                     $d['location'] ?: null, $d['notes'] ?: null, $series, Auth::id() ?: null, date('Y-m-d H:i:s')]
                );
                $id = (int)$pdo->lastInsertId();
                if ($first === 0) {
                    $first = $id;
                }
                foreach ($participants as $pid) {
                    Database::query('INSERT INTO event_players (event_id, player_id) VALUES (?, ?)', [$id, $pid]);
                }
                foreach ($teams as $tid) {
                    Database::query('INSERT INTO event_teams (event_id, team_id) VALUES (?, ?)', [$id, $tid]);
                }
                $start->modify('+7 days');
                if ($end) {
                    $end->modify('+7 days');
                }
                $n++;
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
        return $first;
    }

    /**
     * @param array<string,mixed> $d
     * @param array<int,int> $participants
     */
    public static function update(int $id, array $d, array $participants, array $teams = []): void
    {
        Database::query(
            'UPDATE events SET team_id = ?, type = ?, title = ?, starts_at = ?, ends_at = ?, location = ?, notes = ? WHERE id = ?',
            [$d['team_id'] ?: null, $d['type'], $d['title'], $d['starts_at'], $d['ends_at'] ?: null, $d['location'] ?: null, $d['notes'] ?: null, $id]
        );
        Database::query('DELETE FROM event_players WHERE event_id = ?', [$id]);
        foreach ($participants as $pid) {
            Database::query('INSERT INTO event_players (event_id, player_id) VALUES (?, ?)', [$id, $pid]);
        }
        Database::query('DELETE FROM event_teams WHERE event_id = ?', [$id]);
        foreach ($teams as $tid) {
            Database::query('INSERT INTO event_teams (event_id, team_id) VALUES (?, ?)', [$id, $tid]);
        }
    }

    /**
     * Elimina l'esdeveniment o, si $following, també els següents de la sèrie.
     * Retorna quants se n'han eliminat.
     */
    public static function delete(array $event, bool $following): int
    {
        if ($following && $event['series_id']) {
            return Database::query('DELETE FROM events WHERE series_id = ? AND starts_at >= ?', [$event['series_id'], $event['starts_at']])->rowCount();
        }
        return Database::query('DELETE FROM events WHERE id = ?', [$event['id']])->rowCount();
    }

    /**
     * Jugadors/es que es poden afegir a la llista d'un esdeveniment.
     * @return array<int,array<string,mixed>>
     */
    public static function selectablePlayers(): array
    {
        $ids = TeamScope::ids();
        if (!$ids) {
            return [];
        }
        return Database::all(
            "SELECT p.id, p.first_name, p.last_name, GROUP_CONCAT(DISTINCT t.name ORDER BY t.name SEPARATOR ', ') AS teams
             FROM players p JOIN team_players tp ON tp.player_id = p.id AND tp.left_at IS NULL
             JOIN teams t ON t.id = tp.team_id
             WHERE p.active = 1 AND tp.team_id IN (" . Database::in($ids) . ")
             GROUP BY p.id, p.first_name, p.last_name ORDER BY p.last_name, p.first_name",
            $ids
        );
    }

    /**
     * Valida el formulari. Retorna [dades, repetir fins (Y-m-d|null), participants, equips compartits, errors].
     *  - who = equips: un equip (team_id) o diversos (event_teams)
     *  - who = llista: jugadores concretes (event_players), p. ex. un grup de gimnàs
     * @param array<string,mixed> $in
     * @param array<int,int> $allowedTeams
     * @return array{0:array<string,mixed>,1:?string,2:array<int,int>,3:array<int,int>,4:array<int,string>}
     */
    public static function validate(array $in, array $allowedTeams, bool $allowRepeat): array
    {
        $errors = [];
        $type = (string)($in['type'] ?? 'entrenament');
        $type = isset(self::TYPES[$type]) ? $type : 'entrenament';
        $date = (string)($in['date'] ?? '');
        $startsAt = parse_datetime($date, (string)($in['start_time'] ?? ''));
        $endTime = trim((string)($in['end_time'] ?? ''));
        $endsAt = $endTime !== '' ? parse_datetime($date, $endTime) : null;
        $who = ($in['who'] ?? '') === 'llista' ? 'llista' : 'equips';

        $teams = [];
        $participants = [];
        if ($who === 'llista') {
            $valid = array_map('intval', array_column(self::selectablePlayers(), 'id'));
            $participants = array_values(array_intersect(array_map('intval', (array)($in['participants'] ?? [])), $valid));
            if (!$participants) {
                $errors[] = 'Tria com a mínim un jugador/a per a la llista.';
            }
        } else {
            $teams = array_values(array_intersect(array_map('intval', (array)($in['teams'] ?? [])), $allowedTeams));
            if (!$teams) {
                $errors[] = 'Tria com a mínim un equip.';
            }
        }
        $teamId = count($teams) === 1 ? $teams[0] : 0;
        $shared = count($teams) > 1 ? $teams : [];

        $d = [
            'team_id'   => $teamId,
            'type'      => $type,
            'title'     => trim((string)($in['title'] ?? '')),
            'starts_at' => $startsAt,
            'ends_at'   => $endsAt,
            'location'  => mb_substr(trim((string)($in['location'] ?? '')), 0, 150),
            'notes'     => mb_substr(trim((string)($in['notes'] ?? '')), 0, 2000),
        ];
        if ($d['title'] === '') {
            if ($type === 'gimnas') {
                $d['title'] = 'GYM';
            } elseif ($type === 'entrenament') {
                $d['title'] = $teamId > 0 ? 'Entrenament ' . (string)Database::value('SELECT name FROM teams WHERE id = ?', [$teamId]) : 'Entrenament';
            }
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
        return [$d, $repeatUntil, $participants, $shared, $errors];
    }
}
