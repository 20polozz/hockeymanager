<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Plantilles PHP: View::render('pages/dashboard', [...], 'layouts/app').
 * Les vistes reben les variables extretes i el contingut de la pàgina
 * arriba al layout com a $content.
 */
final class View
{
    /**
     * @param array<string,mixed> $data
     */
    public static function render(string $view, array $data = [], ?string $layout = 'layouts/app'): string
    {
        $content = self::partial($view, $data);
        if ($layout === null) {
            return $content;
        }
        return self::partial($layout, $data + ['content' => $content]);
    }

    /**
     * Renderitza un fragment (component, partial) i en retorna l'HTML.
     * @param array<string,mixed> $data
     */
    public static function partial(string $view, array $data = []): string
    {
        $file = HM_ROOT . '/app/Views/' . $view . '.php';
        if (!is_file($file)) {
            throw new \RuntimeException('Vista no trobada: ' . $view);
        }
        extract($data, EXTR_SKIP);
        ob_start();
        try {
            include $file;
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        return (string)ob_get_clean();
    }
}
