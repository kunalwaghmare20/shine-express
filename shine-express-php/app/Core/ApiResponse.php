<?php

declare(strict_types=1);

namespace App\Core;

final class ApiResponse
{
    /** @param array<string, mixed>|list<mixed>|null $data */
    public static function success(mixed $data = null, int $status = 200, ?string $message = null): never
    {
        self::send([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }

    /** @param array<string, mixed>|null $errors */
    public static function error(string $message, int $status = 400, ?array $errors = null): never
    {
        self::send([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'data' => null,
        ], $status);
    }

    /** @param array<string, mixed> $payload */
    private static function send(array $payload, int $status): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Headers: Authorization, Content-Type, Accept');
        header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
