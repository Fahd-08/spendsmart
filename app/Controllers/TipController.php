<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\TipRepository;

/**
 * Gepubliceerde leerteksten lezen.
 */
final class TipController extends Controller
{
    public function index(): void
    {
        $this->view('tips/index', [
            'title' => 'Tips',
            'tips' => (new TipRepository($this->db()))->published(),
        ]);
    }
}
