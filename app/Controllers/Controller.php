<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;

/**
 * Base dels controladors: renderitzat amb el layout principal.
 */
abstract class Controller
{
    /**
     * @param array<string,mixed> $data  Ha d'incloure 'pageTitle' i, si cal, 'activeNav'.
     */
    protected function view(string $view, array $data = [], string $layout = 'layouts/app'): void
    {
        echo View::render($view, $data, $layout);
    }
}
