<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Auth;
use App\Core\Database;

/**
 * Convocatòries: l'entrenador convoca i cada jugador/a respon
 * (confirmat / rebutjat). La resposta NO és l'assistència real al partit,
 * que es registra a l'acta (match_players).
 */
final class CallupRepository
{
    public const RESPONSES = ['pendent' => ['Pendent', 'muted'], 'confirmat' => ['Confirmada', 'green'], 'rebutjat' => ['Rebutjada', 'red']];

    /**
     * @return array<string,mixed>|null
     */
    public static function forMatch(int $matchId): ?array
    {
        $c = Database::first('SELECT * FROM callups WHERE match_id = ?', [$matchId]);
        if (!$c) {
            return null;
        }
        $c['players'] = Database::all(
            'SELECT cp.player_id, cp.response, cp.response_note, cp.responded_at, p.first_name, p.last_name, p.position
             FROM callup_players cp JOIN players p ON p.id = cp.player_id
             WHERE cp.callup_id = ?
             ORDER BY FIELD(cp.response, \'confirmat\', \'pendent\', \'rebutjat\'), p.position = \'porter\' DESC, p.last_name',
            [$c['id']]
        );
        $c['counts'] = ['confirmat' => 0, 'pendent' => 0, 'rebutjat' => 0];
        foreach ($c['players'] as $p) {
            $c['counts'][$p['response']]++;
        }
        return $c;
    }

    /**
     * Jugadors/es que es poden convocar: plantilla de l'equip i jugadors/es
     * de categories inferiors del club (pugen de categoria).
     * @return array<int,array<string,mixed>>
     */
    public static function eligible(array $match): array
    {
        $sort = Database::value('SELECT c.sort_order FROM teams t LEFT JOIN categories c ON c.id = t.category_id WHERE t.id = ?', [$match['team_id']]);
        $rows = Database::all(
            "SELECT p.id, p.first_name, p.last_name, p.position,
                    MAX(tp.team_id = ?) AS own,
                    MAX(CASE WHEN tp.team_id = ? THEN tp.dorsal END) AS dorsal,
                    GROUP_CONCAT(DISTINCT CASE WHEN tp.team_id <> ? THEN t.name END ORDER BY t.name SEPARATOR ', ') AS other_teams
             FROM players p
             JOIN team_players tp ON tp.player_id = p.id AND tp.left_at IS NULL
             JOIN teams t ON t.id = tp.team_id
             LEFT JOIN categories c ON c.id = t.category_id
             WHERE p.active = 1 AND t.season_id = ?
               AND (tp.team_id = ? OR (? IS NOT NULL AND c.sort_order IS NOT NULL AND c.sort_order <= ?))
             GROUP BY p.id, p.first_name, p.last_name, p.position
             ORDER BY own DESC, MAX(c.sort_order) DESC, p.position = 'porter' DESC, p.last_name, p.first_name",
            [$match['team_id'], $match['team_id'], $match['team_id'], $match['season_id'], $match['team_id'], $sort, $sort]
        );
        return $rows;
    }

    /**
     * Crea o actualitza la convocatòria amb la llista de jugadors/es.
     * Es conserven les respostes de qui ja hi era.
     * @param array<int,int> $playerIds
     */
    public static function save(int $matchId, array $playerIds, string $message, ?string $meetingAt, bool $send): int
    {
        $c = Database::first('SELECT id, sent_at FROM callups WHERE match_id = ?', [$matchId]);
        if ($c) {
            $id = (int)$c['id'];
            Database::query('UPDATE callups SET message = ?, meeting_at = ? WHERE id = ?', [$message !== '' ? $message : null, $meetingAt, $id]);
        } else {
            Database::query(
                'INSERT INTO callups (match_id, message, meeting_at, created_by, created_at) VALUES (?, ?, ?, ?, ?)',
                [$matchId, $message !== '' ? $message : null, $meetingAt, Auth::id() ?: null, date('Y-m-d H:i:s')]
            );
            $id = (int)Database::pdo()->lastInsertId();
        }
        $current = array_map('intval', array_column(Database::all('SELECT player_id FROM callup_players WHERE callup_id = ?', [$id]), 'player_id'));
        foreach (array_diff($playerIds, $current) as $pid) {
            Database::query('INSERT INTO callup_players (callup_id, player_id) VALUES (?, ?)', [$id, $pid]);
        }
        foreach (array_diff($current, $playerIds) as $pid) {
            Database::query('DELETE FROM callup_players WHERE callup_id = ? AND player_id = ?', [$id, $pid]);
        }
        if ($send && (!$c || $c['sent_at'] === null)) {
            Database::query('UPDATE callups SET sent_at = ? WHERE id = ?', [date('Y-m-d H:i:s'), $id]);
        }
        return $id;
    }

    public static function delete(int $matchId): void
    {
        Database::query('DELETE FROM callups WHERE match_id = ?', [$matchId]);
    }

    /**
     * Partits propers amb l'estat de la convocatòria (vista de l'entrenador).
     * @param array<int,int> $teamIds
     * @return array<int,array<string,mixed>>
     */
    public static function upcomingForStaff(array $teamIds): array
    {
        if (!$teamIds) {
            return [];
        }
        return Database::all(
            "SELECT m.id AS match_id, m.opponent, m.starts_at, m.is_home, m.location, t.name AS team_name, t.color,
                    c.id AS callup_id, c.sent_at,
                    SUM(cp.response = 'confirmat') AS confirmed, SUM(cp.response = 'rebutjat') AS declined,
                    SUM(cp.response = 'pendent') AS pending, COUNT(cp.player_id) AS total
             FROM matches m JOIN teams t ON t.id = m.team_id
             LEFT JOIN callups c ON c.match_id = m.id
             LEFT JOIN callup_players cp ON cp.callup_id = c.id
             WHERE m.team_id IN (" . Database::in($teamIds) . ") AND m.status IN ('programat','ajornat') AND m.starts_at >= ?
             GROUP BY m.id, m.opponent, m.starts_at, m.is_home, m.location, t.name, t.color, c.id, c.sent_at
             ORDER BY m.starts_at",
            array_merge($teamIds, [date('Y-m-d 00:00:00')])
        );
    }

    /**
     * Convocatòries enviades a un jugador/a (vista del jugador/a).
     * @return array<int,array<string,mixed>>
     */
    public static function forPlayer(int $playerId): array
    {
        return Database::all(
            "SELECT c.id AS callup_id, c.message, c.meeting_at, cp.response, cp.response_note, cp.responded_at,
                    m.id AS match_id, m.opponent, m.starts_at, m.is_home, m.location, m.competition, m.status,
                    t.name AS team_name, t.color
             FROM callup_players cp
             JOIN callups c ON c.id = cp.callup_id AND c.sent_at IS NOT NULL
             JOIN matches m ON m.id = c.match_id
             JOIN teams t ON t.id = m.team_id
             WHERE cp.player_id = ? AND m.starts_at >= ?
             ORDER BY m.starts_at",
            [$playerId, date('Y-m-d H:i:s', strtotime('-2 days'))]
        );
    }

    /**
     * Desa la resposta d'un jugador/a. Retorna false si no pot respondre.
     */
    public static function respond(int $callupId, int $playerId, string $response, string $note): bool
    {
        $row = Database::first(
            "SELECT cp.player_id, m.starts_at FROM callup_players cp
             JOIN callups c ON c.id = cp.callup_id AND c.sent_at IS NOT NULL
             JOIN matches m ON m.id = c.match_id
             WHERE cp.callup_id = ? AND cp.player_id = ?",
            [$callupId, $playerId]
        );
        if (!$row || strtotime((string)$row['starts_at']) < time()) {
            return false;
        }
        Database::query(
            'UPDATE callup_players SET response = ?, response_note = ?, responded_at = ? WHERE callup_id = ? AND player_id = ?',
            [$response, $note !== '' ? $note : null, date('Y-m-d H:i:s'), $callupId, $playerId]
        );
        return true;
    }
}
