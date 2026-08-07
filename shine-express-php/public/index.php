<?php
/**
 * Shine Express — PHP bootstrap (no Composer).
 * Document root should point to /public on shared hosting.
 *
 * Requires PHP 8.1+ (set in cPanel → MultiPHP Manager).
 */

declare(strict_types=1);

if (PHP_VERSION_ID < 80100) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Shine Express requires PHP 8.1 or newer.\n";
    echo 'This server is running PHP ' . PHP_VERSION . ".\n\n";
    echo "Fix (cPanel):\n";
    echo "1. Open MultiPHP Manager\n";
    echo "2. Select the shine-express folder (or domain)\n";
    echo "3. Set PHP version to 8.1, 8.2, or 8.3\n";
    echo "4. Apply and reload https://kdtechnoservices.com/shine-express/\n";
    exit;
}

define('BASE_PATH', dirname(__DIR__));
define('APP_PATH', BASE_PATH . '/app');
define('STORAGE_PATH', BASE_PATH . '/storage');
define('PUBLIC_PATH', BASE_PATH . '/public');

require BASE_PATH . '/app/Helpers/functions.php';
require BASE_PATH . '/app/Core/Autoloader.php';

App\Core\Autoloader::register(APP_PATH);

$config = require APP_PATH . '/Config/app.php';
date_default_timezone_set($config['timezone'] ?? 'Asia/Kolkata');

if (($config['debug'] ?? false) === true) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
    ini_set('display_errors', '0');
}

App\Core\Session::start();

// CORS preflight for mobile API
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Authorization, Content-Type, Accept');
    header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
    http_response_code(204);
    exit;
}

if (str_starts_with(App\Core\Request::path(), '/api/')) {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Headers: Authorization, Content-Type, Accept');
}

$router = new App\Core\Router();
require APP_PATH . '/Config/routes.php';
require APP_PATH . '/Config/api_routes.php';
$router->dispatch(
    App\Core\Request::method(),
    App\Core\Request::path()
);