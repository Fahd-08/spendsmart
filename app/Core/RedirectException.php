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
    /**
     * @param string $url het adres waar de browser naartoe moet, bijv. '/dashboard'
     */
    public function __construct(private readonly string $url)
    {
        parent::__construct('Redirect naar ' . $url);
    }

    public function url(): string
    {
        return $this->url;
    }

    /**
     * Stuurt de browser door. Status 303 betekent: "ga naar deze pagina met een GET-verzoek".
     * Daardoor wordt een formulier niet opnieuw verstuurd als de gebruiker op vernieuwen drukt.
     */
    public function send(): void
    {
        header('Location: ' . $this->url, true, 303);
    }
}
