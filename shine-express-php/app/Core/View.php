<?php

declare(strict_types=1);

namespace App\Core;

final class View
{
    /**
     * @param array<string, mixed> $data
     */
    public static function render(string $name, array $data = [], ?string $layout = 'layouts/main'): void
    {
        $viewFile = APP_PATH . '/Views/' . str_replace('.', '/', $name) . '.php';
        if (!is_file($viewFile)) {
            Response::abort(500, 'View not found: ' . $name);
        }

        extract($data, EXTR_SKIP);

        ob_start();
        require $viewFile;
        $content = ob_get_clean() ?: '';

        if ($layout === null) {
            echo $content;
            return;
        }

        $layoutFile = APP_PATH . '/Views/' . str_replace('.', '/', $layout) . '.php';
        if (!is_file($layoutFile)) {
            echo $content;
            return;
        }

        require $layoutFile;
    }
}
