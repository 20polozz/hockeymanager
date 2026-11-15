<?php
/**
 * Arrencada de l'aplicació: autoload, configuració, errors i sessió.
 * Compatible amb PHP 7.4+ (sense dependències externes ni Composer).
 */
declare(strict_types=1);

spl_autoload_register(function (string $class): void {
    if (strpos($class, 'App\\') !== 0) {
        return;
    }
    $file = HM_ROOT . '/app/' . str_replace('\\', '/', substr($class, 4)) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

require HM_ROOT . '/app/helpers.php';

use App\Core\App;
use App\Core\Config;

Config::load(HM_ROOT . '/config.php');

date_default_timezone_set((string)Config::get('app.timezone', 'Europe/Madrid'));

$isDev = Config::get('app.env') === 'development';
error_reporting(E_ALL);
ini_set('display_errors', $isDev ? '1' : '0');
ini_set('log_errors', '1');

set_exception_handler(function (Throwable $e) use ($isDev): void {
    error_log('[HockeyManager] ' . get_class($e) . ': ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    App::renderError(500, $isDev ? $e->getMessage() . "\n" . $e->getTraceAsString() : null);
});

// Sessió pròpia: nom i ruta de galeta separats de HockeyFantasy.
if (session_status() === PHP_SESSION_NONE) {
    session_name('hm_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => App::basePath() === '' ? '/' : App::basePath() . '/',
        'secure'   => App::isHttps(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
