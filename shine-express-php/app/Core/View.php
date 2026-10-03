<?php

declare(strict_types=1);

namespace App\Core;

final class View
{
    /**
     * @param array<string, mixed> $data
     */
    public static function render(
        string $name,
        array $data = [],
        ?string $layout = 'layouts/main',
        ?string $viewsPath = null
    ): void {
        $viewFile = self::resolve($name, $viewsPath);
        if ($viewFile === null) {
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

        $layoutFile = self::resolve($layout, $viewsPath);
        if ($layoutFile === null) {
            echo $content;
            return;
        }

        require $layoutFile;
    }

    private static function resolve(string $name, ?string $viewsPath): ?string
    {
        $relative = str_replace('.', '/', $name) . '.php';
        $candidates = [];
        if ($viewsPath !== null && $viewsPath !== '') {
            $candidates[] = rtrim($viewsPath, '/\\') . '/' . $relative;
        }
        $candidates[] = APP_PATH . '/Views/' . $relative;

        foreach ($candidates as $file) {
            if (is_file($file)) {
                return $file;
            }
        }

        return null;
    }
}
