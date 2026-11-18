<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Auth;
use App\Core\Database;

final class UserRepository
{
    public const MIN_PASSWORD = 8;

    /**
     * @return array<int,array<string,mixed>>
     */
    public static function all(string $role = ''): array
    {
        $where = '';
        $params = [];
        if (isset(Auth::roleLabels()[$role])) {
            $where = 'WHERE u.role = ?';
            $params[] = $role;
        }
        $users = Database::all(
            "SELECT u.id, u.name, u.username, u.email, u.role, u.active, u.last_login_at, u.player_id,
                    CONCAT(p.first_name, ' ', p.last_name) AS player_name
             FROM users u LEFT JOIN players p ON p.id = u.player_id
             $where
             ORDER BY u.active DESC, FIELD(u.role, 'admin', 'coordinador', 'entrenador', 'jugador'), u.name",
            $params
        );
        $teams = [];
        foreach (Database::all('SELECT ts.user_id, t.name, t.color FROM team_staff ts JOIN teams t ON t.id = ts.team_id ORDER BY t.name') as $r) {
            $teams[(int)$r['user_id']][] = $r;
        }
        foreach ($users as &$u) {
            $u['teams'] = $teams[(int)$u['id']] ?? [];
        }
        return $users;
    }

    /**
     * @return array<string,mixed>|null
     */
    public static function find(int $id): ?array
    {
        $u = Database::first('SELECT id, name, username, email, role, active, player_id, last_login_at, created_at FROM users WHERE id = ?', [$id]);
        if ($u) {
            $u['staff'] = [];
            foreach (Database::all('SELECT team_id, staff_role FROM team_staff WHERE user_id = ?', [$id]) as $r) {
                $u['staff'][(int)$r['team_id']] = $r['staff_role'];
            }
        }
        return $u;
    }

    /**
     * Jugadors/es actius sense compte (més el ja vinculat, si n'hi ha).
     * @return array<int,array<string,mixed>>
     */
    public static function linkablePlayers(?int $currentPlayerId): array
    {
        return Database::all(
            "SELECT p.id, p.first_name, p.last_name,
                    (SELECT GROUP_CONCAT(t.name ORDER BY t.name SEPARATOR ', ') FROM team_players tp JOIN teams t ON t.id = tp.team_id
                      WHERE tp.player_id = p.id AND tp.left_at IS NULL) AS teams
             FROM players p
             WHERE p.active = 1 AND (p.id = ? OR NOT EXISTS (SELECT 1 FROM users u WHERE u.player_id = p.id))
             ORDER BY p.last_name, p.first_name",
            [$currentPlayerId ?? 0]
        );
    }

    /**
     * @param array<string,mixed> $d
     * @param array<int,string> $staff team_id => principal|ajudant
     */
    public static function create(array $d, string $password, array $staff): int
    {
        Database::query(
            'INSERT INTO users (name, username, email, password_hash, role, player_id, active, created_at) VALUES (?, ?, ?, ?, ?, ?, 1, ?)',
            [$d['name'], $d['username'], $d['email'] ?: null, password_hash($password, PASSWORD_DEFAULT), $d['role'],
             $d['role'] === 'jugador' ? ($d['player_id'] ?: null) : null, date('Y-m-d H:i:s')]
        );
        $id = (int)Database::pdo()->lastInsertId();
        self::syncStaff($id, $d['role'] === 'entrenador' ? $staff : []);
        return $id;
    }

    /**
     * @param array<string,mixed> $d
     * @param array<int,string> $staff
     */
    public static function update(int $id, array $d, ?string $password, array $staff): void
    {
        Database::query(
            'UPDATE users SET name = ?, username = ?, email = ?, role = ?, player_id = ? WHERE id = ?',
            [$d['name'], $d['username'], $d['email'] ?: null, $d['role'], $d['role'] === 'jugador' ? ($d['player_id'] ?: null) : null, $id]
        );
        if ($password !== null) {
            Database::query('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), $id]);
        }
        self::syncStaff($id, $d['role'] === 'entrenador' ? $staff : []);
    }

    /** Elimina el compte (la fitxa de jugador/a no s'esborra; el que va crear queda sense autor). */
    public static function delete(int $id): void
    {
        Database::query('UPDATE player_absences SET created_by = NULL WHERE created_by = ?', [$id]);
        Database::query('DELETE FROM users WHERE id = ?', [$id]);
    }

    /** Administradors actius sense comptar $exceptId. */
    public static function countActiveAdmins(int $exceptId): int
    {
        return (int)Database::value("SELECT COUNT(*) FROM users WHERE role = 'admin' AND active = 1 AND id <> ?", [$exceptId]);
    }

    public static function setActive(int $id, bool $active): void
    {
        Database::query('UPDATE users SET active = ? WHERE id = ?', [$active ? 1 : 0, $id]);
    }

    /**
     * @param array<int,string> $staff
     */
    private static function syncStaff(int $userId, array $staff): void
    {
        Database::query('DELETE FROM team_staff WHERE user_id = ?', [$userId]);
        foreach ($staff as $teamId => $role) {
            Database::query('INSERT INTO team_staff (team_id, user_id, staff_role) VALUES (?, ?, ?)', [$teamId, $userId, $role === 'ajudant' ? 'ajudant' : 'principal']);
        }
    }

    /**
     * Valida el formulari d'usuari. Retorna [dades, contrasenya|null, equips, errors].
     * @param array<string,mixed> $in
     * @param array<int,int> $teamIds equips que es poden assignar
     * @param array<int,string> $allowedRoles
     * @return array{0:array<string,mixed>,1:?string,2:array<int,string>,3:array<int,string>}
     */
    public static function validate(array $in, ?int $id, array $teamIds, array $allowedRoles): array
    {
        $d = [
            'name'      => trim((string)($in['name'] ?? '')),
            'username'  => mb_strtolower(trim((string)($in['username'] ?? ''))),
            'email'     => mb_strtolower(trim((string)($in['email'] ?? ''))),
            'role'      => (string)($in['role'] ?? ''),
            'player_id' => (int)($in['player_id'] ?? 0),
        ];
        $password = (string)($in['password'] ?? '');
        $errors = [];

        if (mb_strlen($d['name']) < 2 || mb_strlen($d['name']) > 120) {
            $errors[] = 'Escriu el nom (2-120 caràcters).';
        }
        if (!preg_match('/^[a-z0-9._-]{3,50}$/', $d['username'])) {
            $errors[] = 'El nom d\'usuari ha de tenir 3-50 caràcters: lletres sense accents, números, punts, guions o guions baixos.';
        } elseif (Database::value('SELECT 1 FROM users WHERE username = ? AND id <> ?', [$d['username'], $id ?? 0])) {
            $errors[] = 'Aquest nom d\'usuari ja existeix.';
        }
        if ($d['email'] !== '') {
            if (!filter_var($d['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'El correu no és vàlid.';
            } elseif (Database::value('SELECT 1 FROM users WHERE email = ? AND id <> ?', [$d['email'], $id ?? 0])) {
                $errors[] = 'Aquest correu ja el fa servir un altre usuari.';
            }
        }
        if (!in_array($d['role'], $allowedRoles, true)) {
            $errors[] = 'Tria un rol vàlid.';
        }
        if ($id === null || $password !== '') {
            if (strlen($password) < self::MIN_PASSWORD) {
                $errors[] = 'La contrasenya ha de tenir com a mínim ' . self::MIN_PASSWORD . ' caràcters.';
            }
        }
        if ($d['role'] === 'jugador' && $d['player_id'] > 0) {
            $taken = Database::value('SELECT 1 FROM users WHERE player_id = ? AND id <> ?', [$d['player_id'], $id ?? 0]);
            $exists = Database::value('SELECT 1 FROM players WHERE id = ? AND active = 1', [$d['player_id']]);
            if (!$exists || $taken) {
                $errors[] = 'Aquesta fitxa de jugador/a no està disponible.';
            }
        }

        $staff = [];
        $roles = is_array($in['staff_role'] ?? null) ? $in['staff_role'] : [];
        foreach ((array)($in['teams'] ?? []) as $tid) {
            $tid = (int)$tid;
            if (in_array($tid, $teamIds, true)) {
                $staff[$tid] = (string)($roles[$tid] ?? 'principal') === 'ajudant' ? 'ajudant' : 'principal';
            }
        }
        return [$d, ($id === null || $password !== '') ? $password : null, $staff, $errors];
    }
}
