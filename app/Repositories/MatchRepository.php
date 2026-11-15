<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Auth;
use App\Core\Database;

final class MatchRepository
{
    public const STATUSES = [
        'programat'  => ['Programat', 'blue'],
        'jugat'      => ['Disputat', 'green'],
        'ajornat'    => ['Ajornat', 'orange'],
        'cancel·lat' => ['Cancel·lat', 'red'],
    ];

    /**
     * @param array<int,int> $teamIds
     * @return array<int,array<string,mixed>>
     */
    public static function list(array $teamIds, string $tab, int $seasonId): array
    {
        if (!$teamIds) {
            return [];
        }
        $where = 'm.team_id IN (' . Database::in($teamIds) . ')';
        $params = $teamIds;
        if ($seasonId > 0) {
            $where .= ' AND m.season_id = ?';
            $params[] = $seasonId;
        }
        if ($tab === 'disputats') {
            $where .= " AND m.status = 'jugat'";
            $order = 'm.starts_at DESC';
        } else {
            $where .= " AND m.status IN ('programat','ajornat')";
            $order = 'm.starts_at ASC';
        }
        return Database::all(
            "SELECT m.*, t.name AS team_name, t.color,
                    c.id AS callup_id, c.sent_at,
                    (SELECT COUNT(*) FROM callup_players cp WHERE cp.callup_id = c.id) AS called,
                    (SELECT COUNT(*) FROM callup_players cp WHERE cp.callup_id = c.id AND cp.response = 'confirmat') AS confirmed
             FROM matches m JOIN teams t ON t.id = m.team_id
             LEFT JOIN callups c ON c.match_id = m.id
             WHERE $where ORDER BY $order",
            $params
        );
    }

    /**
     * @return array<string,mixed>|null
     */
    public static function find(int $id): ?array
    {
        return Database::first(
            'SELECT m.*, t.name AS team_name, t.color, t.category_id, s.name AS season
             FROM matches m JOIN teams t ON t.id = m.team_id JOIN seasons s ON s.id = m.season_id
             WHERE m.id = ?',
            [$id]
        );
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function seasons(): array
    {
        return Database::all('SELECT id, name, is_current FROM seasons ORDER BY starts_on DESC');
    }

    /**
     * @param array<string,mixed> $d
     */
    public static function create(array $d): int
    {
        $season = (int)Database::value('SELECT season_id FROM teams WHERE id = ?', [$d['team_id']]);
        Database::query(
            'INSERT INTO matches (team_id, season_id, opponent, competition, starts_at, location, is_home, goals_for, goals_against, status, created_by, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [$d['team_id'], $season, $d['opponent'], $d['competition'] ?: null, $d['starts_at'], $d['location'] ?: null, $d['is_home'],
             $d['goals_for'], $d['goals_against'], $d['status'], Auth::id() ?: null, date('Y-m-d H:i:s')]
        );
        return (int)Database::pdo()->lastInsertId();
    }

    /**
     * @param array<string,mixed> $d
     */
    public static function update(int $id, array $d): void
    {
        Database::query(
            'UPDATE matches SET team_id = ?, opponent = ?, competition = ?, starts_at = ?, location = ?, is_home = ?,
                    goals_for = ?, goals_against = ?, status = ? WHERE id = ?',
            [$d['team_id'], $d['opponent'], $d['competition'] ?: null, $d['starts_at'], $d['location'] ?: null, $d['is_home'],
             $d['goals_for'], $d['goals_against'], $d['status'], $id]
        );
    }

    public static function delete(int $id): void
    {
        Database::query('DELETE FROM matches WHERE id = ?', [$id]);
    }

    /**
     * Competicions ja fetes servir (per suggerir-les al formulari).
     * @return array<int,string>
     */
    public static function competitions(): array
    {
        return array_column(Database::all('SELECT DISTINCT competition FROM matches WHERE competition IS NOT NULL ORDER BY competition'), 'competition');
    }

    /**
     * @param array<string,mixed> $in
     * @param array<int,int> $allowedTeams
     * @return array{0:array<string,mixed>,1:array<int,string>}
     */
    public static function validate(array $in, array $allowedTeams): array
    {
        $status = (string)($in['status'] ?? 'programat');
        $gf = trim((string)($in['goals_for'] ?? ''));
        $ga = trim((string)($in['goals_against'] ?? ''));
        $d = [
            'team_id'       => (int)($in['team_id'] ?? 0),
            'opponent'      => trim((string)($in['opponent'] ?? '')),
            'competition'   => mb_substr(trim((string)($in['competition'] ?? '')), 0, 120),
            'starts_at'     => parse_datetime((string)($in['date'] ?? ''), (string)($in['time'] ?? '')),
            'location'      => mb_substr(trim((string)($in['location'] ?? '')), 0, 150),
            'is_home'       => (string)($in['is_home'] ?? '1') === '0' ? 0 : 1,
            'status'        => isset(self::STATUSES[$status]) ? $status : 'programat',
            'goals_for'     => null,
            'goals_against' => null,
        ];
        $errors = [];
        if (!in_array($d['team_id'], $allowedTeams, true)) {
            $errors[] = 'Tria un equip.';
        }
        if (mb_strlen($d['opponent']) < 2 || mb_strlen($d['opponent']) > 120) {
            $errors[] = 'Escriu el nom del rival (2-120 caràcters).';
        }
        if ($d['starts_at'] === null) {
            $errors[] = 'La data o l\'hora no són vàlides.';
        }
        if ($d['status'] === 'jugat') {
            if (!ctype_digit($gf) || !ctype_digit($ga) || (int)$gf > 99 || (int)$ga > 99) {
                $errors[] = 'Per a un partit disputat cal posar el resultat (gols a favor i en contra).';
            } else {
                $d['goals_for'] = (int)$gf;
                $d['goals_against'] = (int)$ga;
            }
        }
        return [$d, $errors];
    }

    // ---------- Acta ----------

    /**
     * Jugadors/es per a l'acta: plantilla de l'equip, convocats/des i qui ja hi consti.
     * @return array<int,array<string,mixed>>
     */
    public static function sheet(array $match): array
    {
        return Database::all(
            "SELECT p.id, p.first_name, p.last_name, p.position, tp.dorsal,
                    mp.played, mp.goals, mp.assists, mp.blue_cards, mp.red_cards,
                    mp.gk_goals_against, mp.gk_saves, mp.gk_shots, mp.gk_penalties_faced, mp.gk_penalties_saved,
                    mp.gk_free_hits_faced, mp.gk_free_hits_saved,
                    (mp.player_id IS NOT NULL) AS in_sheet,
                    cp.response AS callup_response,
                    (tp.id IS NULL) AS guest
             FROM players p
             LEFT JOIN team_players tp ON tp.player_id = p.id AND tp.team_id = ? AND tp.left_at IS NULL
             LEFT JOIN match_players mp ON mp.player_id = p.id AND mp.match_id = ?
             LEFT JOIN callups c ON c.match_id = ?
             LEFT JOIN callup_players cp ON cp.callup_id = c.id AND cp.player_id = p.id
             WHERE (p.active = 1 AND tp.id IS NOT NULL) OR mp.player_id IS NOT NULL OR cp.player_id IS NOT NULL
             ORDER BY guest, p.position = 'porter' DESC, tp.dorsal IS NULL, tp.dorsal, p.last_name",
            [$match['team_id'], $match['id'], $match['id']]
        );
    }

    /**
     * Desa l'acta. Només es guarden les files de qui ha jugat.
     * @param array<int,array<string,mixed>> $rows player_id => dades
     */
    public static function saveSheet(int $matchId, array $rows): void
    {
        $pdo = Database::pdo();
        $pdo->beginTransaction();
        try {
            Database::query('DELETE FROM match_players WHERE match_id = ?', [$matchId]);
            foreach ($rows as $pid => $r) {
                Database::query(
                    'INSERT INTO match_players (match_id, player_id, played, goals, assists, blue_cards, red_cards,
                        gk_goals_against, gk_saves, gk_shots, gk_penalties_faced, gk_penalties_saved, gk_free_hits_faced, gk_free_hits_saved)
                     VALUES (?, ?, 1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    [$matchId, $pid, $r['goals'], $r['assists'], $r['blue_cards'], $r['red_cards'],
                     $r['gk_goals_against'], $r['gk_saves'], $r['gk_shots'], $r['gk_penalties_faced'], $r['gk_penalties_saved'],
                     $r['gk_free_hits_faced'], $r['gk_free_hits_saved']]
                );
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Resum de l'acta per a la fitxa del partit.
     * @return array<int,array<string,mixed>>
     */
    public static function sheetSummary(int $matchId): array
    {
        return Database::all(
            'SELECT mp.*, p.first_name, p.last_name, p.position
             FROM match_players mp JOIN players p ON p.id = mp.player_id
             WHERE mp.match_id = ? ORDER BY mp.goals DESC, mp.assists DESC, p.last_name',
            [$matchId]
        );
    }
}
