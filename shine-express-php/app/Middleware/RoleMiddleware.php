<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Response;

final class RoleMiddleware
{
    /** @param list<string> $roles */
    public function __construct(private array $roles)
    {
    }

    public function handle(): void
    {
        $role = Auth::role();
        if ($role === null || !in_array($role, $this->roles, true)) {
            Response::abort(403, 'You do not have access to this area.');
        }
    }
}
