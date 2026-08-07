<?php

declare(strict_types=1);

/**
 * CLI bootstrap (seed scripts, etc.) — not for web requests.
 */

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('STORAGE_PATH', BASE_PATH . '/storage');
define('PUBLIC_PATH', BASE_PATH . '/public');

require APP_PATH . '/Helpers/functions.php';
require APP_PATH . '/Core/Autoloader.php';

App\Core\Autoloader::register(APP_PATH);

$config = require APP_PATH . '/Config/app.php';
date_default_timezone_set($config['timezone'] ?? 'Asia/Kolkata');
