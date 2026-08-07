<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\ApiAuth;
use App\Core\ApiResponse;

final class ApiAuthMiddleware
{
    public function handle(): void
    {
        if (!ApiAuth::attemptFromHeader()) {
            ApiResponse::error('Unauthenticated', 401);
        }
    }
}
