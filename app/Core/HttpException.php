<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Fout die als nette foutpagina met de juiste HTTP-statuscode wordt getoond.
 *
 * Gebruikte codes: 401 niet ingelogd, 403 geen toegang, 404 niet gevonden,
 * 405 verkeerde methode, 419 CSRF-token ongeldig.
 */
final class HttpException extends RuntimeException
{
    public function __construct(private readonly int $status, string $message)
    {
        parent::__construct($message, $status);
    }

    /**
     * De HTTP-statuscode, bijv. 404.
     */
    public function status(): int
    {
        return $this->status;
    }
}
