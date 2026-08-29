<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Services\ReportService;

final class DashboardController extends Controller
{
    public function admin(): void
    {
        $data = (new ReportService())->summary();
        $this->view('dashboard/admin', array_merge($data, [
            'title' => 'Admin Dashboard',
            'user' => Auth::user(),
            'base' => '/admin',
            'canAcceptPending' => true,
        ]), 'layouts/dashboard');
    }

    public function branchManager(): void
    {
        $data = (new ReportService())->summary(Auth::branchId());
        $this->view('dashboard/admin', array_merge($data, [
            'title' => 'Branch Dashboard',
            'user' => Auth::user(),
            'base' => '/branch-manager',
            'canAcceptPending' => false,
        ]), 'layouts/dashboard');
    }
}
