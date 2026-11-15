<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

final class PlayerRepository
{
    /**
     * Jugadors actius dels equips indicats.
     * @param array<int,int> $teamIds
     * @return array<int,array<string,mixed>>
     */
    public static function listForTeams(array $teamIds, string $search = ''): array
    {
        if (!$teamIds) {
            return [];
        }
        $params = $teamIds;
        $where = '';
        if ($search !== '') {
            $where = " AND CONCAT(p.first_name, ' ', p.last_name) LIKE ?";
            $params[] = '%' . addcslashes($search, '%_\\') . '%';
        }
        // Una fila per jugador encara que estigui en diversos equips
        $rows = Database::all(
            'SELECT p.id, p.first_name, p.last_name, p.birth_date, p.position,
                    tp.dorsal, t.id AS team_id, t.name AS team_name, t.color, c.sort_order
             FROM team_players tp
             JOIN players p ON p.id = tp.player_id AND p.active = 1
             JOIN teams t ON t.id = tp.team_id
             LEFT JOIN categories c ON c.id = t.category_id
             WHERE tp.left_at IS NULL AND tp.team_id IN (' . Database::in($teamIds) . ')' . $where . '
             ORDER BY c.sort_order, t.name, tp.dorsal IS NULL, tp.dorsal, p.last_name, p.first_name',
            $params
        );
        $players = [];
        foreach ($rows as $r) {
            $id = (int)$r['id'];
            if (!isset($players[$id])) {
                $players[$id] = $r + ['teams' => []];
            }
            $players[$id]['teams'][] = ['id' => (int)$r['team_id'], 'name' => $r['team_name'], 'color' => $r['color'], 'dorsal' => $r['dorsal']];
        }
        return array_values($players);
    }

    /**
     * @return array<string,mixed>|null
     */
    public static function find(int $id): ?array
    {
        return Database::first('SELECT * FROM players WHERE id = ?', [$id]);
    }

    /**
     * Equips (actuals i anteriors) on ha jugat.
     * @return array<int,array<string,mixed>>
     */
    public static function teamHistory(int $playerId): array
    {
        return Database::all(
            'SELECT tp.team_id, tp.dorsal, tp.joined_on, tp.left_at, t.name AS team_name, t.color,
                    c.name AS category, s.name AS season
             FROM team_players tp
             JOIN teams t ON t.id = tp.team_id
             JOIN seasons s ON s.id = t.season_id
             LEFT JOIN categories c ON c.id = t.category_id
             WHERE tp.player_id = ?
             ORDER BY tp.joined_on DESC',
            [$playerId]
        );
    }

    /**
     * Recompte d'assistència de tota la temporada.
     * @return array<string,int>
     */
    public static function attendanceSummary(int $playerId): array
    {
        $out = ['present' => 0, 'absent' => 0, 'justificat' => 0, 'retard' => 0];
        foreach (Database::all('SELECT status, COUNT(*) AS n FROM attendance WHERE player_id = ? GROUP BY status', [$playerId]) as $r) {
            $out[$r['status']] = (int)$r['n'];
        }
        return $out;
    }

    /**
     * Equips actuals del jugador.
     * @return array<int,int>
     */
    public static function currentTeamIds(int $playerId): array
    {
        return array_map('intval', array_column(
            Database::all('SELECT team_id FROM team_players WHERE player_id = ? AND left_at IS NULL', [$playerId]),
            'team_id'
        ));
    }
}
