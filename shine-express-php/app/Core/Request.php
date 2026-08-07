<?php

declare(strict_types=1);

namespace App\Core;

final class Request
{
    public static function method(): string
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $override = $_POST['_method'] ?? $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] ?? null;
        if ($override && in_array(strtoupper((string) $override), ['PUT', 'PATCH', 'DELETE'], true)) {
            return strtoupper((string) $override);
        }
        return strtoupper($method);
    }

    public static function path(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';

        // Prefer explicit APP_BASE_PATH (e.g. /shine-express) for subdirectory installs
        $basePath = rtrim((string) env_file('APP_BASE_PATH', ''), '/');
        if ($basePath !== '' && str_starts_with($path, $basePath)) {
            $path = substr($path, strlen($basePath)) ?: '/';
        } else {
            // Fallback: strip dirname(SCRIPT_NAME) e.g. /shine-express/public
            $scriptName = dirname($_SERVER['SCRIPT_NAME'] ?? '');
            if ($scriptName !== '/' && $scriptName !== '\\' && str_starts_with($path, $scriptName)) {
                $path = substr($path, strlen($scriptName)) ?: '/';
            }
        }

        $path = '/' . trim($path, '/');
        return $path === '/' ? '/' : rtrim($path, '/');
    }

    public static function all(): array
    {
        $json = self::json();
        return array_merge($_GET, $_POST, $json);
    }

    /** @return array<string, mixed> */
    public static function json(): array
    {
        static $cached = null;
        if ($cached !== null) {
            return $cached;
        }
        $raw = file_get_contents('php://input') ?: '';
        if ($raw === '') {
            $cached = [];
            return $cached;
        }
        $decoded = json_decode($raw, true);
        $cached = is_array($decoded) ? $decoded : [];
        return $cached;
    }

    public static function input(string $key, mixed $default = null): mixed
    {
        $all = self::all();
        return $all[$key] ?? $default;
    }

    public static function isPost(): bool
    {
        return self::method() === 'POST';
    }

    public static function ip(): string
    {
        return $_SERVER['HTTP_X_FORWARDED_FOR']
            ?? $_SERVER['REMOTE_ADDR']
            ?? '0.0.0.0';
    }
}
