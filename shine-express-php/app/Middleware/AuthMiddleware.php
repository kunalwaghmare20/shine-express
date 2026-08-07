<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Response;
use App\Core\Session;

final class AuthMiddleware
{
    public function handle(): void
    {
        if (!Auth::check()) {
            Session::flash('error', 'Please sign in to continue.');
            Response::redirect(url('/login'));
        }
    }
}
