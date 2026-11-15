<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Executa les migracions de database/migrations en ordre i en guarda
 * el registre a la taula "migrations". Es pot tornar a executar sense perill.
 */
final class Migrator
{
    private const DIR = HM_ROOT . '/database/migrations';

    /**
     * @return array<int,string> noms de migracions pendents
     */
    public static function pending(): array
    {
        self::ensureTable();
        $done = array_column(Database::all('SELECT name FROM migrations'), 'name');
        $all = [];
        foreach (glob(self::DIR . '/*.php') ?: [] as $file) {
            $all[] = basename($file, '.php');
        }
        sort($all, SORT_STRING);
        return array_values(array_diff($all, $done));
    }

    /**
     * @return array<int,string> migracions aplicades ara
     */
    public static function run(): array
    {
        $applied = [];
        foreach (self::pending() as $name) {
            $statements = require self::DIR . '/' . $name . '.php';
            // MySQL fa commit implícit amb CREATE TABLE: no fem servir transaccions.
            foreach ($statements as $sql) {
                Database::pdo()->exec($sql);
            }
            Database::query('INSERT INTO migrations (name, applied_at) VALUES (?, ?)', [$name, date('Y-m-d H:i:s')]);
            $applied[] = $name;
        }
        return $applied;
    }

    private static function ensureTable(): void
    {
        Database::pdo()->exec(
            'CREATE TABLE IF NOT EXISTS migrations (
                name VARCHAR(120) NOT NULL PRIMARY KEY,
                applied_at DATETIME NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }
}
