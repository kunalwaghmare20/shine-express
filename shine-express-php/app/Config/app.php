<?php

declare(strict_types=1);

/**
 * Application settings.
 * Prefer values from .env (copy .env.example → .env on the server).
 */

return [
    'name' => env_file('APP_NAME', 'Shine Express'),
    'url' => env_file('APP_URL', ''), // e.g. https://example.com or https://example.com/subdir/public
    'env' => env_file('APP_ENV', 'local'),
    'debug' => filter_var(env_file('APP_DEBUG', 'true'), FILTER_VALIDATE_BOOLEAN),
    'timezone' => env_file('APP_TIMEZONE', 'Asia/Kolkata'),
    'session_name' => env_file('SESSION_NAME', 'shine_express_session'),
];
