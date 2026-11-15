<?php
declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOStatement;

/**
 * Connexió PDO a la base de dades pròpia de HockeyManager.
 */
final class Database
{
    /** @var PDO|null */
    private static $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            if (!Config::isLoaded()) {
                throw new \RuntimeException('Falta config.php. Copia config.example.php i omple les dades de la base de dades.');
            }
            $dsn = sprintf(
                'mysql:host=%s;port=%d;dbname=%s;charset=%s',
                (string)Config::get('db.host', 'localhost'),
                (int)Config::get('db.port', 3306),
                (string)Config::get('db.name', ''),
                (string)Config::get('db.charset', 'utf8mb4')
            );
            self::$pdo = new PDO($dsn, (string)Config::get('db.user', ''), (string)Config::get('db.pass', ''), [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        }
        return self::$pdo;
    }

    /**
     * @param array<int|string,mixed> $params
     */
    public static function query(string $sql, array $params = []): PDOStatement
    {
        $st = self::pdo()->prepare($sql);
        $st->execute($params);
        return $st;
    }

    /**
     * @param array<int|string,mixed> $params
     * @return array<string,mixed>|null
     */
    public static function first(string $sql, array $params = []): ?array
    {
        $row = self::query($sql, $params)->fetch();
        return $row === false ? null : $row;
    }

    /**
     * @param array<int|string,mixed> $params
     * @return array<int,array<string,mixed>>
     */
    public static function all(string $sql, array $params = []): array
    {
        return self::query($sql, $params)->fetchAll();
    }

    /**
     * @param array<int|string,mixed> $params
     * @return mixed
     */
    public static function value(string $sql, array $params = [])
    {
        $v = self::query($sql, $params)->fetchColumn();
        return $v === false ? null : $v;
    }

    /**
     * Placeholders "?,?,?" per a una clàusula IN.
     * @param array<int,mixed> $values
     */
    public static function in(array $values): string
    {
        return implode(',', array_fill(0, max(1, count($values)), '?'));
    }

    public static function tableExists(string $table): bool
    {
        $st = self::pdo()->prepare('SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?');
        $st->execute([$table]);
        return (bool)$st->fetchColumn();
    }
}
