<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\TipRepository;

/**
 * Gepubliceerde leerteksten lezen (FE-11, kant van de gebruiker).
 */
final class TipController extends Controller
{
    /**
     * GET /tips: alle gepubliceerde teksten. Concepten zijn hier niet te zien.
     */
    public function index(): void
    {
        $this->view('tips/index', [
            'title' => 'Tips',
            'tips' => (new TipRepository($this->db()))->published(),
        ]);
    }
}
