<?php

declare(strict_types=1);

namespace App\Website;

use App\Core\View;

/**
 * Base controller for the public website / CMS module.
 * Views resolve from app/Website/Views first, then app/Views (for the admin dashboard layout).
 */
abstract class Controller extends \App\Core\Controller
{
    /**
     * @param array<string, mixed> $data
     */
    protected function view(string $name, array $data = [], ?string $layout = 'layouts/main'): void
    {
        View::render($name, $data, $layout, APP_PATH . '/Website/Views');
    }
}
