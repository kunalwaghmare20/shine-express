<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;

final class HomeController extends Controller
{
    public function index(): void
    {
        $this->view('home/index', [
            'title' => 'Welcome',
            'headline' => 'Shine Express',
            'tagline' => 'Multi-service business management — PHP MVC edition for shared hosting.',
        ]);
    }
}
