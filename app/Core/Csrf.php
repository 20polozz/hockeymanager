<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Protecció CSRF: cada formulari POST porta un testimoni de la sessió.
 */
final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['hm_csrf'])) {
            $_SESSION['hm_csrf'] = bin2hex(random_bytes(32));
        }
        return (string)$_SESSION['hm_csrf'];
    }

    public static function verify(): void
    {
        $sent = (string)($_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));
        if ($sent === '' || !hash_equals(self::token(), $sent)) {
            throw new HttpException(419, 'La sessió ha caducat. Torna a carregar la pàgina i prova-ho de nou.');
        }
    }
}
