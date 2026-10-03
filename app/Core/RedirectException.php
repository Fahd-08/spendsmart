<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Een redirect wordt als exception gegooid en in public/index.php verstuurd.
 * Zo stopt de code direct (net als exit), maar kunnen tests controleren waarheen wordt doorgestuurd.
 */
final class RedirectException extends RuntimeException
{
    public function __construct(private readonly string $url)
    {
        parent::__construct('Redirect naar ' . $url);
    }

    public function url(): string
    {
        return $this->url;
    }

    public function send(): void
    {
        header('Location: ' . $this->url, true, 303);
    }
}
