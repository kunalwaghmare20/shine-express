<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Services\ReportService;

final class ReportController extends Controller
{
    public function index(): void
    {
        $branchId = Auth::role() === 'BRANCH_MANAGER' ? Auth::branchId() : null;
        $data = (new ReportService())->summary($branchId);
        $this->view('reports/index', array_merge($data, [
            'title' => 'Reports',
            'user' => Auth::user(),
        ]), 'layouts/dashboard');
    }
}
