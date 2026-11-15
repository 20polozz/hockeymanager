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
            'SELECT t.id, t.name, t.kind, t.color, c.name AS category, s.name AS season,
                    (SELECT COUNT(*) FROM team_players tp WHERE tp.team_id = t.id AND tp.left_at IS NULL) AS players,
                    (SELECT COUNT(*) FROM team_players tp JOIN players p ON p.id = tp.player_id
                       WHERE tp.team_id = t.id AND tp.left_at IS NULL AND p.position = \'porter\') AS goalkeepers
             FROM teams t
             JOIN seasons s ON s.id = t.season_id
             LEFT JOIN categories c ON c.id = t.category_id
             WHERE t.id IN (' . Database::in($teamIds) . ')
             ORDER BY t.kind, c.sort_order, t.name',
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

    /**
     * @return array<string,mixed>|null
     */
    public static function find(int $id): ?array
    {
        return Database::first('SELECT * FROM teams WHERE id = ?', [$id]);
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function categories(): array
    {
        return Database::all('SELECT id, name FROM categories ORDER BY sort_order');
    }

    public static function currentSeasonId(): int
    {
        return (int)Database::value('SELECT id FROM seasons WHERE is_current = 1 ORDER BY id DESC LIMIT 1');
    }

    public static function create(string $name, ?int $categoryId, string $color, string $kind = 'equip'): int
    {
        Database::query(
            'INSERT INTO teams (season_id, category_id, name, kind, color, created_at) VALUES (?, ?, ?, ?, ?, ?)',
            [self::currentSeasonId(), $categoryId, $name, $kind, $color, date('Y-m-d H:i:s')]
        );
        return (int)Database::pdo()->lastInsertId();
    }

    public static function update(int $id, string $name, ?int $categoryId, string $color, string $kind = 'equip'): void
    {
        Database::query('UPDATE teams SET name = ?, category_id = ?, color = ?, kind = ? WHERE id = ?', [$name, $categoryId, $color, $kind, $id]);
    }

    /**
     * Membres actuals d'un equip o grup.
     * @return array<int,int>
     */
    public static function memberIds(int $teamId): array
    {
        return array_map('intval', array_column(Database::all('SELECT player_id FROM team_players WHERE team_id = ? AND left_at IS NULL', [$teamId]), 'player_id'));
    }

    /**
     * Desa els membres d'un grup (qui surt queda a l'historial).
     * @param array<int,int> $playerIds
     */
    public static function syncMembers(int $teamId, array $playerIds): void
    {
        $current = self::memberIds($teamId);
        foreach (array_diff($playerIds, $current) as $pid) {
            Database::query('INSERT INTO team_players (team_id, player_id, joined_on) VALUES (?, ?, ?)', [$teamId, $pid, date('Y-m-d')]);
        }
        foreach (array_diff($current, $playerIds) as $pid) {
            Database::query('UPDATE team_players SET left_at = ? WHERE team_id = ? AND player_id = ? AND left_at IS NULL', [date('Y-m-d'), $teamId, $pid]);
        }
    }

    /**
     * Totes les jugadores actives amb els seus equips (per triar membres d'un grup).
     * @return array<int,array<string,mixed>>
     */
    public static function allPlayers(): array
    {
        return Database::all(
            "SELECT p.id, p.first_name, p.last_name,
                    (SELECT GROUP_CONCAT(t.name ORDER BY t.name SEPARATOR ', ') FROM team_players tp JOIN teams t ON t.id = tp.team_id
                      WHERE tp.player_id = p.id AND tp.left_at IS NULL AND t.kind = 'equip') AS teams
             FROM players p WHERE p.active = 1 ORDER BY p.last_name, p.first_name"
        );
    }

    /**
     * Motiu pel qual no es pot eliminar (o null si es pot).
     */
    public static function deleteBlocker(int $id): ?string
    {
        if ((int)Database::value('SELECT COUNT(*) FROM team_players WHERE team_id = ?', [$id]) > 0) {
            return 'Aquest equip o grup té (o ha tingut) jugadors/es i no es pot eliminar perquè se\'n conserva l\'historial.';
        }
        if ((int)Database::value('SELECT COUNT(*) FROM matches WHERE team_id = ?', [$id]) > 0
            || (int)Database::value('SELECT COUNT(*) FROM events WHERE team_id = ?', [$id]) > 0) {
            return 'Aquest equip ja té partits o entrenaments registrats i no es pot eliminar.';
        }
        return null;
    }

    public static function delete(int $id): void
    {
        Database::query('DELETE FROM teams WHERE id = ?', [$id]);
    }

    /**
     * Valida les dades del formulari d'equip. Retorna [dades, errors].
     * @param array<string,mixed> $in
     * @return array{0:array<string,mixed>,1:array<int,string>}
     */
    public static function validate(array $in): array
    {
        $data = [
            'name'        => trim((string)($in['name'] ?? '')),
            'category_id' => (int)($in['category_id'] ?? 0),
            'color'       => strtolower(trim((string)($in['color'] ?? '#1e3a8a'))),
            'kind'        => (string)($in['kind'] ?? 'equip') === 'grup' ? 'grup' : 'equip',
        ];
        $errors = [];
        $len = mb_strlen($data['name']);
        if ($len < 2 || $len > 80) {
            $errors[] = 'El nom de l\'equip ha de tenir entre 2 i 80 caràcters.';
        }
        if ($data['category_id'] > 0 && !Database::value('SELECT 1 FROM categories WHERE id = ?', [$data['category_id']])) {
            $errors[] = 'La categoria no és vàlida.';
        }
        if (!preg_match('/^#[0-9a-f]{6}$/', $data['color'])) {
            $errors[] = 'El color no és vàlid.';
        }
        if (self::currentSeasonId() === 0) {
            $errors[] = 'No hi ha cap temporada activa.';
        }
        return [$data, $errors];
    }
}
