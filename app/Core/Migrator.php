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
        // Evita que dues peticions simultànies apliquin la mateixa migració
        if ((int)Database::value('SELECT GET_LOCK(?, 10)', ['hm_migrations']) !== 1) {
            return [];
        }
        try {
            return self::runLocked();
        } finally {
            Database::value('SELECT RELEASE_LOCK(?)', ['hm_migrations']);
        }
    }

    /**
     * @return array<int,string>
     */
    private static function runLocked(): array
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

    /**
     * Aplica les migracions pendents si l'app ja està instal·lada.
     * Un error no atura l'aplicació: queda al registre i es reintenta.
     */
    public static function autoRun(): void
    {
        if (!Config::isLoaded()) {
            return;
        }
        try {
            if (!Database::tableExists('users')) {
                return; // encara no instal·lada: ho fa /install
            }
            $applied = self::run();
            if ($applied) {
                error_log('[HockeyManager] migracions aplicades: ' . implode(', ', $applied));
            }
        } catch (\Throwable $e) {
            error_log('[HockeyManager] migració fallida: ' . $e->getMessage());
        }
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
