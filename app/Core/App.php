<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Informació de la petició i de l'entorn (ruta base, URL actual...).
 */
final class App
{
    /** @var string|null */
    private static $basePath = null;

    /**
     * Ruta base de l'aplicació: "/hockeymanager" ara, "" en un domini propi.
     */
    public static function basePath(): string
    {
        if (self::$basePath === null) {
            $configured = Config::get('app.base_path');
            if (is_string($configured)) {
                self::$basePath = rtrim($configured, '/');
            } else {
                $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
                self::$basePath = rtrim($dir, '/.');
            }
        }
        return self::$basePath;
    }

    /**
     * Ruta demanada dins de l'app, sense la ruta base ni la query string.
     */
    public static function path(): string
    {
        $uri = (string)parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $uri = rawurldecode($uri);
        $base = self::basePath();
        if ($base !== '' && strpos($uri, $base) === 0) {
            $uri = substr($uri, strlen($base));
        }
        if ($uri === 'index.php' || $uri === '/index.php') {
            $uri = '/';
        }
        $uri = '/' . trim($uri, '/');
        return $uri;
    }

    public static function isHttps(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    }

    public static function renderError(int $code, ?string $detail = null): void
    {
        if (!headers_sent()) {
            // 419 (sessió caducada) no és estàndard: Apache el convertiria en 500.
            http_response_code($code === 419 ? 403 : $code);
        }
        try {
            echo View::render('pages/errors/error', [
                'code'   => $code,
                'detail' => $detail,
            ], 'layouts/minimal');
        } catch (\Throwable $e) {
            echo 'Error ' . $code;
        }
    }
}
