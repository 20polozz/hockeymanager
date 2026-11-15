<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;

/**
 * Paràmetres del club guardats a la BD (taula settings).
 */
final class Settings
{
    /** @var array<string,string>|null */
    private static $cache = null;

    public static function get(string $key, string $default = ''): string
    {
        if (self::$cache === null) {
            self::$cache = [];
            try {
                foreach (Database::all('SELECT `key`, `value` FROM settings') as $row) {
                    self::$cache[(string)$row['key']] = (string)$row['value'];
                }
            } catch (\Throwable $e) {
                // Taula encara no creada (abans de la instal·lació)
            }
        }
        return self::$cache[$key] ?? $default;
    }

    public static function set(string $key, string $value): void
    {
        Database::query(
            'INSERT INTO settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)',
            [$key, $value]
        );
        if (self::$cache !== null) {
            self::$cache[$key] = $value;
        }
    }

    /** La BD conté dades de demostració carregades per l'instal·lador. */
    public static function hasDemoData(): bool
    {
        return self::get('demo_data') === '1';
    }
}
