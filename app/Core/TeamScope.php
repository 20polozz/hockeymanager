<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Quins equips pot veure l'usuari actual.
 *  - Coordinador: tots els equips de la temporada actual.
 *  - Entrenador: els equips que té assignats (team_staff).
 *  - Jugador: l'equip (o equips) on està inscrit ara mateix.
 */
final class TeamScope
{
    /** @var array<int,array<string,mixed>>|null */
    private static $teams = null;

    /**
     * @return array<int,array<string,mixed>> equips accessibles [id, name, category]
     */
    public static function teams(): array
    {
        if (self::$teams !== null) {
            return self::$teams;
        }
        $user = Auth::user();
        if (!$user) {
            return self::$teams = [];
        }
        $base = 'SELECT t.id, t.name, t.color, c.name AS category
                 FROM teams t
                 LEFT JOIN categories c ON c.id = t.category_id
                 JOIN seasons s ON s.id = t.season_id AND s.is_current = 1';

        switch ($user['role']) {
            case Auth::ROLE_COORDINATOR:
                $rows = Database::all($base . ' ORDER BY c.sort_order, t.name');
                break;
            case Auth::ROLE_COACH:
                $rows = Database::all(
                    $base . ' JOIN team_staff ts ON ts.team_id = t.id AND ts.user_id = ? ORDER BY c.sort_order, t.name',
                    [$user['id']]
                );
                break;
            default:
                $rows = Database::all(
                    $base . ' JOIN team_players tp ON tp.team_id = t.id AND tp.player_id = ? AND tp.left_at IS NULL ORDER BY c.sort_order, t.name',
                    [(int)$user['player_id']]
                );
        }
        return self::$teams = $rows;
    }

    /**
     * @return array<int,int>
     */
    public static function ids(): array
    {
        return array_map(function (array $t): int {
            return (int)$t['id'];
        }, self::teams());
    }

    public static function canAccess(int $teamId): bool
    {
        return in_array($teamId, self::ids(), true);
    }
}
