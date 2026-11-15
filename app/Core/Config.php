<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Lectura de config.php amb notació de punts: Config::get('db.host').
 */
final class Config
{
    /** @var array<string,mixed> */
    private static $data = [];
    /** @var bool */
    private static $loaded = false;

    public static function load(string $file): void
    {
        if (is_file($file)) {
            $data = require $file;
            if (is_array($data)) {
                self::$data = $data;
                self::$loaded = true;
            }
            return;
        }
        self::loadFromHost(dirname($file) . '/../config.php');
    }

    /**
     * Mentre HockeyManager viu dins de hockeyfantasy.eu i no té config.php propi,
     * reaprofita el servidor, l'usuari i la contrasenya de la BD de l'amfitrió,
     * però amb la seva PRÒPIA base de dades ("hockeymanager"). La clau
     * d'instal·lació és l'ADMIN_KEY de l'amfitrió.
     * En moure l'app a un domini propi, n'hi ha prou de crear config.php.
     */
    private static function loadFromHost(string $hostConfig): void
    {
        if (!is_file($hostConfig)) {
            return;
        }
        require_once $hostConfig;
        if (!defined('DB_HOST') || !defined('DB_USER') || !defined('DB_PASS')) {
            return;
        }
        self::$data = [
            'db' => [
                'host'    => (string)constant('DB_HOST'),
                'port'    => 3306,
                'name'    => defined('HM_DB_NAME') ? (string)constant('HM_DB_NAME') : 'hockeymanager',
                'user'    => (string)constant('DB_USER'),
                'pass'    => (string)constant('DB_PASS'),
                'charset' => 'utf8mb4',
            ],
            'app' => [
                'name'      => 'HockeyManager',
                'club_name' => 'Club Hoquei Patins',
                'env'       => 'production',
                'timezone'  => 'Europe/Madrid',
                'base_path' => null,
            ],
            'install_key' => defined('ADMIN_KEY') ? (string)constant('ADMIN_KEY') : '',
        ];
        self::$loaded = true;
    }

    public static function isLoaded(): bool
    {
        return self::$loaded;
    }

    /**
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        $value = self::$data;
        foreach (explode('.', $key) as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                return $default;
            }
            $value = $value[$part];
        }
        return $value;
    }
}
