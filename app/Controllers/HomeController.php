<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Repositories\TipRepository;

final class HomeController extends Controller
{
    public function index(): void
    {
        if (Auth::check()) {
            $this->redirect(Auth::homePath());
        }

        $this->view('home/index', [
            'title' => 'Welkom',
            'tip' => (new TipRepository($this->db()))->randomPublished(),
        ]);
    }
}
