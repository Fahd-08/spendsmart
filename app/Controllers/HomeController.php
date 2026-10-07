<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Repositories\TipRepository;

/**
 * De openbare startpagina.
 */
final class HomeController extends Controller
{
    /**
     * GET /: startpagina voor bezoekers. Ben je al ingelogd, dan ga je meteen naar je eigen startpagina.
     */
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
