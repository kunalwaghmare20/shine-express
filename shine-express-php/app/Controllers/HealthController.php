<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use PDOException;

final class HealthController extends Controller
{
    public function index(): void
    {
        $database = 'unconfigured';
        $status = 'degraded';
        $http = 503;

        try {
            $config = require APP_PATH . '/Config/database.php';
            if (!empty($config['database'])) {
                Database::connection()->query('SELECT 1');
                $database = 'up';
                $status = 'ok';
                $http = 200;
            }
        } catch (PDOException $e) {
            $database = 'down';
            $status = 'error';
        } catch (\Throwable $e) {
            $database = 'down';
            $status = 'error';
        }

        $this->json([
            'success' => $http === 200,
            'data' => [
                'status' => $status,
                'database' => $database,
                'php' => PHP_VERSION,
            ],
        ], $http);
    }
}
