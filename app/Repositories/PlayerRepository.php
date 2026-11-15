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

    /**
     * Equips actuals amb el dorsal: [team_id => dorsal|null].
     * @return array<int,int|null>
     */
    public static function currentMemberships(int $playerId): array
    {
        $out = [];
        foreach (Database::all('SELECT team_id, dorsal FROM team_players WHERE player_id = ? AND left_at IS NULL', [$playerId]) as $r) {
            $out[(int)$r['team_id']] = $r['dorsal'] === null ? null : (int)$r['dorsal'];
        }
        return $out;
    }

    /**
     * @param array<string,mixed> $d  first_name, last_name, birth_date, position
     */
    public static function create(array $d): int
    {
        Database::query(
            'INSERT INTO players (first_name, last_name, birth_date, position, created_at) VALUES (?, ?, ?, ?, ?)',
            [$d['first_name'], $d['last_name'], $d['birth_date'] ?: null, $d['position'], date('Y-m-d H:i:s')]
        );
        return (int)Database::pdo()->lastInsertId();
    }

    /**
     * @param array<string,mixed> $d
     */
    public static function update(int $id, array $d): void
    {
        Database::query(
            'UPDATE players SET first_name = ?, last_name = ?, birth_date = ?, position = ? WHERE id = ?',
            [$d['first_name'], $d['last_name'], $d['birth_date'] ?: null, $d['position'], $id]
        );
    }

    /**
     * Desa els equips actuals. Els equips que es treuen queden a l'historial
     * (left_at = avui) en lloc d'esborrar-se.
     * @param array<int,int|null> $teams  [team_id => dorsal|null]
     */
    public static function syncTeams(int $playerId, array $teams): void
    {
        $today = date('Y-m-d');
        $current = self::currentMemberships($playerId);
        foreach ($teams as $teamId => $dorsal) {
            if (array_key_exists($teamId, $current)) {
                Database::query('UPDATE team_players SET dorsal = ? WHERE player_id = ? AND team_id = ? AND left_at IS NULL', [$dorsal, $playerId, $teamId]);
            } else {
                Database::query('INSERT INTO team_players (team_id, player_id, dorsal, joined_on) VALUES (?, ?, ?, ?)', [$teamId, $playerId, $dorsal, $today]);
            }
        }
        foreach (array_diff(array_keys($current), array_keys($teams)) as $teamId) {
            Database::query('UPDATE team_players SET left_at = ? WHERE player_id = ? AND team_id = ? AND left_at IS NULL', [$today, $playerId, $teamId]);
        }
    }

    /** Baixa del club: deixa d'aparèixer però es conserva l'historial. */
    public static function deactivate(int $playerId): void
    {
        Database::query('UPDATE players SET active = 0 WHERE id = ?', [$playerId]);
        Database::query('UPDATE team_players SET left_at = ? WHERE player_id = ? AND left_at IS NULL', [date('Y-m-d'), $playerId]);
    }

    /**
     * Valida el formulari. Retorna [dades, equips, errors].
     * @param array<string,mixed> $in
     * @param array<int,int> $allowedTeams
     * @return array{0:array<string,mixed>,1:array<int,int|null>,2:array<int,string>}
     */
    public static function validate(array $in, array $allowedTeams): array
    {
        $d = [
            'first_name' => trim((string)($in['first_name'] ?? '')),
            'last_name'  => trim((string)($in['last_name'] ?? '')),
            'birth_date' => trim((string)($in['birth_date'] ?? '')),
            'position'   => (string)($in['position'] ?? 'jugador') === 'porter' ? 'porter' : 'jugador',
        ];
        $errors = [];
        if (mb_strlen($d['first_name']) < 1 || mb_strlen($d['first_name']) > 60) {
            $errors[] = 'Escriu el nom (màxim 60 caràcters).';
        }
        if (mb_strlen($d['last_name']) < 1 || mb_strlen($d['last_name']) > 100) {
            $errors[] = 'Escriu els cognoms (màxim 100 caràcters).';
        }
        if ($d['birth_date'] !== '') {
            $dt = \DateTime::createFromFormat('!Y-m-d', $d['birth_date']);
            if (!$dt || $dt->format('Y-m-d') !== $d['birth_date'] || $dt > new \DateTime('today') || (int)$dt->format('Y') < 1940) {
                $errors[] = 'La data de naixement no és vàlida.';
            }
        }

        $teams = [];
        $dorsals = is_array($in['dorsal'] ?? null) ? $in['dorsal'] : [];
        foreach ((array)($in['teams'] ?? []) as $tid) {
            $tid = (int)$tid;
            if (!in_array($tid, $allowedTeams, true)) {
                continue;
            }
            $raw = trim((string)($dorsals[$tid] ?? ''));
            if ($raw === '') {
                $teams[$tid] = null;
            } elseif (ctype_digit($raw) && (int)$raw <= 99) {
                $teams[$tid] = (int)$raw;
            } else {
                $errors[] = 'Els dorsals han de ser números del 0 al 99.';
                $teams[$tid] = null;
            }
        }
        if (!$teams) {
            $errors[] = 'Tria com a mínim un equip.';
        }
        return [$d, $teams, array_values(array_unique($errors))];
    }
}
