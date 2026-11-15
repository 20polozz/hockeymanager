<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Auth;
use App\Core\Database;

/**
 * Registre d'activitat (es mostra a "Últimes activitats" del dashboard).
 */
final class ActivityLog
{
    public static function add(string $type, string $description, ?int $teamId = null): void
    {
        try {
            Database::query(
                'INSERT INTO activity_log (user_id, team_id, type, description, created_at) VALUES (?, ?, ?, ?, ?)',
                [Auth::id() ?: null, $teamId, $type, mb_substr($description, 0, 255), date('Y-m-d H:i:s')]
            );
        } catch (\Throwable $e) {
            error_log('[HockeyManager] activity_log: ' . $e->getMessage());
        }
    }

    /**
     * Activitat recent dels equips indicats (i la general del club, sense equip,
     * només per al coordinador). Els inicis de sessió no es mostren.
     * @param array<int,int> $teamIds
     * @return array<int,array<string,mixed>>
     */
    public static function recent(array $teamIds, bool $includeClubWide, int $limit = 6): array
    {
        $where = [];
        $params = [];
        if ($teamIds) {
            $where[] = 'a.team_id IN (' . Database::in($teamIds) . ')';
            $params = $teamIds;
        }
        if ($includeClubWide) {
            $where[] = 'a.team_id IS NULL';
        }
        if (!$where) {
            return [];
        }
        $params[] = $limit;
        return Database::all(
            "SELECT a.type, a.description, a.created_at, u.name AS user_name, t.name AS team_name
             FROM activity_log a
             LEFT JOIN users u ON u.id = a.user_id
             LEFT JOIN teams t ON t.id = a.team_id
             WHERE a.type <> 'login' AND (" . implode(' OR ', $where) . ')
             ORDER BY a.created_at DESC, a.id DESC
             LIMIT ?',
            $params
        );
    }
}
