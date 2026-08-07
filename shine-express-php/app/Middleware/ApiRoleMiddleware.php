<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\ApiAuth;
use App\Core\ApiResponse;

final class ApiRoleMiddleware
{
    /** @param list<string> $roles */
    public function __construct(private array $roles)
    {
    }

    public function handle(): void
    {
        $role = ApiAuth::role();
        if ($role === null || !in_array($role, $this->roles, true)) {
            ApiResponse::error('Forbidden', 403);
        }
    }
}
