<?php

declare(strict_types=1);

namespace App\Core;

final class Response
{
    public static function redirect(string $url, int $status = 302): never
    {
        header('Location: ' . $url, true, $status);
        exit;
    }

    /** @param array<string, mixed> $data */
    public static function json(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    public static function abort(int $status, string $message = ''): never
    {
        http_response_code($status);
        $view = APP_PATH . '/Views/errors/' . $status . '.php';
        if (is_file($view)) {
            $title = $message !== '' ? $message : (string) $status;
            require $view;
            exit;
        }
        echo htmlspecialchars($message !== '' ? $message : 'Error ' . $status);
        exit;
    }
}
