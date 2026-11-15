<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Auth;
use App\Core\Database;

/**
 * Absències i baixes temporals: períodes en què una jugadora no està disponible.
 */
final class AbsenceRepository
{
    public const TYPES = [
        'lesio'    => 'Lesió',
        'malaltia' => 'Malaltia',
        'viatge'   => 'Viatge',
        'estudis'  => 'Estudis / feina',
        'personal' => 'Motius personals',
        'altres'   => 'Altres',
    ];

    public static function label(array $a): string
    {
        return self::TYPES[$a['type']] ?? 'Absència';
    }

    /** Text curt del període: «fins al 30/11», «del 2/12 al 9/12», «sense data de tornada». */
    public static function period(array $a): string
    {
        $from = strtotime((string)$a['starts_on']);
        $to = $a['ends_on'] ? strtotime((string)$a['ends_on']) : null;
        if ($from > strtotime('today')) {
            return $to ? 'del ' . date('d/m', $from) . ' al ' . date('d/m', $to) : 'a partir del ' . date('d/m', $from);
        }
        return $to ? 'fins al ' . date('d/m', $to) : 'sense data de tornada';
    }

    /** @return array<int,array<string,mixed>> */
    public static function forPlayer(int $playerId): array
    {
        return Database::all(
            'SELECT a.*, u.name AS created_by_name,
                    (a.starts_on <= CURDATE() AND (a.ends_on IS NULL OR a.ends_on >= CURDATE())) AS is_current,
                    (a.ends_on IS NOT NULL AND a.ends_on < CURDATE()) AS is_past
             FROM player_absences a LEFT JOIN users u ON u.id = a.created_by
             WHERE a.player_id = ?
             ORDER BY is_past, a.starts_on DESC LIMIT 30',
            [$playerId]
        );
    }

    public static function find(int $id): ?array
    {
        return Database::first('SELECT * FROM player_absences WHERE id = ?', [$id]);
    }

    /**
     * Absències vigents en una data, per jugadora.
     * @param array<int,int> $playerIds
     * @return array<int,array<string,mixed>> player_id => absència
     */
    public static function onDate(string $date, array $playerIds): array
    {
        $playerIds = array_values(array_filter(array_map('intval', $playerIds)));
        if (!$playerIds) {
            return [];
        }
        $rows = Database::all(
            'SELECT * FROM player_absences
             WHERE player_id IN (' . Database::in($playerIds) . ') AND starts_on <= ? AND (ends_on IS NULL OR ends_on >= ?)
             ORDER BY starts_on',
            array_merge($playerIds, [$date, $date])
        );
        $out = [];
        foreach ($rows as $r) {
            $out[(int)$r['player_id']] = $r;
        }
        return $out;
    }

    /**
     * Jugadores no disponibles ara o en els pròxims $days dies (dels equips donats).
     * @param array<int,int> $teamIds
     * @return array<int,array<string,mixed>>
     */
    public static function upcoming(array $teamIds, int $days = 14): array
    {
        if (!$teamIds) {
            return [];
        }
        return Database::all(
            'SELECT a.*, p.first_name, p.last_name,
                    (a.starts_on <= CURDATE()) AS is_current
             FROM player_absences a JOIN players p ON p.id = a.player_id AND p.active = 1
             WHERE (a.ends_on IS NULL OR a.ends_on >= CURDATE())
               AND a.starts_on <= DATE_ADD(CURDATE(), INTERVAL ' . max(0, $days) . ' DAY)
               AND EXISTS (SELECT 1 FROM team_players tp WHERE tp.player_id = p.id AND tp.left_at IS NULL
                           AND tp.team_id IN (' . Database::in($teamIds) . '))
             ORDER BY is_current DESC, a.starts_on, p.last_name',
            $teamIds
        );
    }

    /** @return int id */
    public static function create(int $playerId, array $d): int
    {
        Database::query(
            'INSERT INTO player_absences (player_id, type, starts_on, ends_on, note, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())',
            [$playerId, $d['type'], $d['starts_on'], $d['ends_on'], $d['note'], Auth::id()]
        );
        return (int)Database::pdo()->lastInsertId();
    }

    public static function update(int $id, array $d): void
    {
        Database::query(
            'UPDATE player_absences SET type = ?, starts_on = ?, ends_on = ?, note = ? WHERE id = ?',
            [$d['type'], $d['starts_on'], $d['ends_on'], $d['note'], $id]
        );
    }

    public static function delete(int $id): void
    {
        Database::query('DELETE FROM player_absences WHERE id = ?', [$id]);
    }

    /** Dóna per acabada avui una absència (la jugadora ja ha tornat). */
    public static function finish(int $id): void
    {
        Database::query('UPDATE player_absences SET ends_on = GREATEST(starts_on, CURDATE() - INTERVAL 1 DAY) WHERE id = ?', [$id]);
    }

    /**
     * @return array{0:array<string,mixed>,1:array<int,string>}
     */
    public static function validate(array $in): array
    {
        $errors = [];
        $type = (string)($in['type'] ?? '');
        if (!isset(self::TYPES[$type])) {
            $errors[] = 'Tria el motiu de l\'absència.';
        }
        $from = \DateTime::createFromFormat('!Y-m-d', (string)($in['starts_on'] ?? ''));
        if (!$from) {
            $errors[] = 'Indica des de quan.';
        }
        $to = null;
        if (empty($in['open_ended']) && trim((string)($in['ends_on'] ?? '')) !== '') {
            $to = \DateTime::createFromFormat('!Y-m-d', (string)$in['ends_on']);
            if (!$to) {
                $errors[] = 'La data de tornada no és vàlida.';
            } elseif ($from && $to < $from) {
                $errors[] = 'La data de tornada no pot ser anterior a l\'inici.';
            }
        } elseif (empty($in['open_ended'])) {
            $errors[] = 'Indica fins quan o marca «Sense data de tornada».';
        }
        return [[
            'type'      => $type,
            'starts_on' => $from ? $from->format('Y-m-d') : null,
            'ends_on'   => $to ? $to->format('Y-m-d') : null,
            'note'      => mb_substr(trim((string)($in['note'] ?? '')), 0, 255) ?: null,
        ], $errors];
    }
}
