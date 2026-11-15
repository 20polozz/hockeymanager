<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Permisos per rol (app/permissions.php). Es comproven al servidor a cada
 * ruta; amagar opcions del menú és només una conseqüència, no la protecció.
 */
final class Gate
{
    /** @var array<string,array<int,string>>|null */
    private static $matrix = null;

    public static function allows(string $permission): bool
    {
        if (self::$matrix === null) {
            self::$matrix = require HM_ROOT . '/app/permissions.php';
        }
        $roles = self::$matrix[$permission] ?? [];
        return Auth::check() && in_array(Auth::role(), $roles, true);
    }

    public static function authorize(string $permission): void
    {
        if (!self::allows($permission)) {
            throw new HttpException(403);
        }
    }
}
