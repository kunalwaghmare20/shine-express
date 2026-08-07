<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Simple PSR-4-style autoloader for App\* classes (no Composer).
 */
final class Autoloader
{
    private static string $basePath;

    public static function register(string $basePath): void
    {
        self::$basePath = rtrim($basePath, '/\\');
        spl_autoload_register([self::class, 'load']);
    }

    public static function load(string $class): void
    {
        $prefix = 'App\\';
        if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
            return;
        }

        $relative = substr($class, strlen($prefix));
        $file = self::$basePath . '/' . str_replace('\\', '/', $relative) . '.php';

        if (is_file($file)) {
            require $file;
        }
    }
}
