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
        }
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
