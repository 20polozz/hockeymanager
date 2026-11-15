<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Router mínim amb paràmetres {id} i middleware d'autenticació i permisos.
 *
 * Opcions de cada ruta:
 *   'auth'  => true (per defecte) | false  — cal sessió iniciada
 *   'guest' => true                         — només sense sessió (login)
 *   'can'   => 'permis.clau'                — permís requerit (vegeu app/permissions.php)
 */
final class Router
{
    /** @var array<int,array<string,mixed>> */
    private $routes = [];

    /**
     * @param array<string,mixed> $options
     */
    public function get(string $path, string $handler, array $options = []): void
    {
        $this->add('GET', $path, $handler, $options);
    }

    /**
     * @param array<string,mixed> $options
     */
    public function post(string $path, string $handler, array $options = []): void
    {
        $this->add('POST', $path, $handler, $options);
    }

    /**
     * @param array<string,mixed> $options
     */
    private function add(string $method, string $path, string $handler, array $options): void
    {
        $regex = '#^' . preg_replace('#\{([a-z_]+)\}#', '(?P<$1>[0-9]+)', rtrim($path, '/') ?: '/') . '$#';
        $this->routes[] = [
            'method'  => $method,
            'regex'   => $regex,
            'handler' => $handler,
            'options' => $options + ['auth' => true],
        ];
    }

    public function dispatch(string $method, string $path): void
    {
        try {
            $allowed = false;
            foreach ($this->routes as $route) {
                if (!preg_match($route['regex'], $path, $m)) {
                    continue;
                }
                $allowed = true;
                if ($route['method'] !== $method) {
                    continue;
                }
                $params = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);
                $this->runMiddleware($route['options'], $method);
                $this->call($route['handler'], array_map('intval', $params));
                return;
            }
            throw new HttpException($allowed ? 405 : 404);
        } catch (HttpException $e) {
            App::renderError($e->status(), $e->getMessage() !== '' ? $e->getMessage() : null);
        }
    }

    /**
     * @param array<string,mixed> $options
     */
    private function runMiddleware(array $options, string $method): void
    {
        if (!empty($options['guest'])) {
            if (Auth::check()) {
                redirect('/');
            }
        } elseif (!empty($options['auth'])) {
            if (!Auth::check()) {
                if ($method === 'GET') {
                    $_SESSION['hm_intended'] = App::path();
                }
                redirect('/login');
            }
            if (!empty($options['can'])) {
                Gate::authorize((string)$options['can']);
            }
        }

        if ($method === 'POST') {
            Csrf::verify();
        }
    }

    /**
     * @param array<string,int> $params
     */
    private function call(string $handler, array $params): void
    {
        [$class, $action] = explode('@', $handler);
        $fqcn = 'App\\Controllers\\' . $class;
        $controller = new $fqcn();
        $controller->$action(...array_values($params));
    }
}
