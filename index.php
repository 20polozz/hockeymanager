<?php
/**
 * HockeyManager — front controller. Totes les peticions arriben aquí.
 */
declare(strict_types=1);

define('HM_ROOT', __DIR__);

require HM_ROOT . '/app/bootstrap.php';

$router = new App\Core\Router();
require HM_ROOT . '/app/routes.php';

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', App\Core\App::path());
