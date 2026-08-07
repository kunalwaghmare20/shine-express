<?php

declare(strict_types=1);

return [
    'host' => env_file('DB_HOST', '127.0.0.1'),
    'port' => env_file('DB_PORT', '3306'),
    'database' => env_file('DB_DATABASE', 'shine_express'),
    'username' => env_file('DB_USERNAME', 'root'),
    'password' => env_file('DB_PASSWORD', ''),
    'charset' => env_file('DB_CHARSET', 'utf8mb4'),
];
