<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Fout die als nette foutpagina met de juiste HTTP-statuscode wordt getoond.
 */
final class HttpException extends RuntimeException
{
    public function __construct(private readonly int $status, string $message)
    {
        parent::__construct($message, $status);
    }

    public function status(): int
    {
        return $this->status;
    }
}
