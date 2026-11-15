<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Sessió d'usuari: login, logout i usuari actual.
 */
final class Auth
{
    public const ROLE_COORDINATOR = 'coordinador';
    public const ROLE_COACH = 'entrenador';
    public const ROLE_PLAYER = 'jugador';

    private const MAX_ATTEMPTS = 5;
    private const LOCK_MINUTES = 15;

    /** @var array<string,mixed>|null|false  false = encara no carregat */
    private static $user = false;

    /**
     * @return array<string,mixed>|null
     */
    public static function user(): ?array
    {
        if (self::$user === false) {
            self::$user = null;
            $id = (int)($_SESSION['hm_user_id'] ?? 0);
            if ($id > 0) {
                $row = Database::first(
                    'SELECT id, name, email, role, player_id, active FROM users WHERE id = ?',
                    [$id]
                );
                if ($row && (int)$row['active'] === 1) {
                    self::$user = $row;
                } else {
                    unset($_SESSION['hm_user_id']);
                }
            }
        }
        return self::$user;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function id(): int
    {
        $u = self::user();
        return $u ? (int)$u['id'] : 0;
    }

    public static function role(): string
    {
        $u = self::user();
        return $u ? (string)$u['role'] : '';
    }

    public static function hasRole(string ...$roles): bool
    {
        return in_array(self::role(), $roles, true);
    }

    /**
     * Intenta iniciar sessió. Retorna un missatge d'error o null si tot va bé.
     */
    public static function attempt(string $email, string $password): ?string
    {
        $email = mb_strtolower(trim($email));
        $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
        $since = date('Y-m-d H:i:s', time() - self::LOCK_MINUTES * 60);

        $fails = (int)Database::value(
            'SELECT COUNT(*) FROM login_attempts WHERE (ip = ? OR email = ?) AND attempted_at > ?',
            [$ip, $email, $since]
        );
        if ($fails >= self::MAX_ATTEMPTS) {
            return 'Massa intents fallits. Torna-ho a provar d\'aquí a ' . self::LOCK_MINUTES . ' minuts.';
        }

        $user = Database::first('SELECT id, password_hash, active FROM users WHERE email = ?', [$email]);
        if (!$user || !password_verify($password, (string)$user['password_hash'])) {
            Database::query(
                'INSERT INTO login_attempts (ip, email, attempted_at) VALUES (?, ?, ?)',
                [$ip, $email, date('Y-m-d H:i:s')]
            );
            return 'Correu o contrasenya incorrectes.';
        }
        if ((int)$user['active'] !== 1) {
            return 'Aquest compte està desactivat. Parla amb el coordinador del club.';
        }

        if (password_needs_rehash((string)$user['password_hash'], PASSWORD_DEFAULT)) {
            Database::query('UPDATE users SET password_hash = ? WHERE id = ?', [password_hash($password, PASSWORD_DEFAULT), $user['id']]);
        }
        Database::query('DELETE FROM login_attempts WHERE email = ?', [$email]);
        self::login((int)$user['id']);
        return null;
    }

    public static function login(int $userId): void
    {
        session_regenerate_id(true);
        $_SESSION['hm_user_id'] = $userId;
        unset($_SESSION['hm_active_team']);
        self::$user = false;
        Database::query('UPDATE users SET last_login_at = ? WHERE id = ?', [date('Y-m-d H:i:s'), $userId]);
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], (bool)$p['secure'], (bool)$p['httponly']);
        }
        session_destroy();
        self::$user = null;
    }

    /**
     * @return array<string,string>
     */
    public static function roleLabels(): array
    {
        return [
            self::ROLE_COORDINATOR => 'Coordinador',
            self::ROLE_COACH       => 'Entrenador',
            self::ROLE_PLAYER      => 'Jugador',
        ];
    }
}
