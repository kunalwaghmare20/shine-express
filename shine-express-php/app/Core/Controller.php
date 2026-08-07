<?php

declare(strict_types=1);

namespace App\Core;

abstract class Controller
{
    /**
     * @param array<string, mixed> $data
     */
    protected function view(string $name, array $data = [], ?string $layout = 'layouts/main'): void
    {
        View::render($name, $data, $layout);
    }

    /**
     * @param array<string, mixed> $data
     */
    protected function json(array $data, int $status = 200): never
    {
        Response::json($data, $status);
    }

    protected function redirect(string $url): never
    {
        Response::redirect(url($url));
    }

    protected function back(): never
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? url('/');
        Response::redirect($referer);
    }
}
