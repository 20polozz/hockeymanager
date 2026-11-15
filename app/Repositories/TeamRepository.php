<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

final class TeamRepository
{
    /**
     * Equips amb nombre de jugadors i staff.
     * @param array<int,int> $teamIds
     * @return array<int,array<string,mixed>>
     */
    public static function overview(array $teamIds): array
    {
        if (!$teamIds) {
            return [];
        }
        $teams = Database::all(
            'SELECT t.id, t.name, t.color, c.name AS category, s.name AS season,
                    (SELECT COUNT(*) FROM team_players tp WHERE tp.team_id = t.id AND tp.left_at IS NULL) AS players,
                    (SELECT COUNT(*) FROM team_players tp JOIN players p ON p.id = tp.player_id
                       WHERE tp.team_id = t.id AND tp.left_at IS NULL AND p.position = \'porter\') AS goalkeepers
             FROM teams t
             JOIN seasons s ON s.id = t.season_id
             LEFT JOIN categories c ON c.id = t.category_id
             WHERE t.id IN (' . Database::in($teamIds) . ')
             ORDER BY c.sort_order, t.name',
            $teamIds
        );
        $staff = Database::all(
            'SELECT ts.team_id, ts.staff_role, u.name
             FROM team_staff ts JOIN users u ON u.id = ts.user_id
             WHERE ts.team_id IN (' . Database::in($teamIds) . ')
             ORDER BY ts.staff_role, u.name',
            $teamIds
        );
        foreach ($teams as &$t) {
            $t['staff'] = array_values(array_filter($staff, function (array $s) use ($t): bool {
                return (int)$s['team_id'] === (int)$t['id'];
            }));
        }
        return $teams;
    }
}
