<?php

declare(strict_types=1);

namespace App\Core;

use PDOException;
use Throwable;

/**
 * Zet fouten om in een begrijpelijke foutpagina. Technische details worden
 * alleen gelogd (en alleen getoond als APP_DEBUG=true).
 */
final class ErrorHandler
{
    /** Titel op de foutpagina per statuscode. */
    private const TITLES = [
        401 => 'Niet ingelogd',
        403 => 'Geen toegang',
        404 => 'Niet gevonden',
        405 => 'Actie niet toegestaan',
        419 => 'Sessie verlopen',
        500 => 'Er ging iets mis',
    ];

    /**
     * Bepaalt welke foutpagina de gebruiker ziet.
     */
    public static function handle(Throwable $exception): void
    {
        // Een bewust gegooide fout (bijv. 404): de melding is al gebruikersvriendelijk.
        if ($exception instanceof HttpException) {
            self::render($exception->status(), $exception->getMessage());

            return;
        }

        // Onverwachte fout: volledige details in het logbestand voor de ontwikkelaar.
        error_log((string) $exception);

        // De gebruiker krijgt een algemene melding, zonder technische details (die kunnen een aanvaller helpen).
        $message = $exception instanceof PDOException
            ? 'De database is op dit moment niet bereikbaar. Probeer het later opnieuw.'
            : 'Er ging onverwacht iets mis. Probeer het later opnieuw.';

        // Alleen lokaal (APP_DEBUG=true) de technische fout erbij zetten.
        if (config('app.debug')) {
            $message .= ' [' . get_class($exception) . ': ' . $exception->getMessage() . ']';
        }

        self::render(500, $message);
    }

    /**
     * Toont de foutpagina met de juiste statuscode.
     */
    private static function render(int $status, string $message): void
    {
        http_response_code($status);

        try {
            echo View::render('errors/error', [
                'title' => self::TITLES[$status] ?? 'Fout',
                'status' => $status,
                'message' => $message,
            ]);
        } catch (Throwable $renderError) {
            // Laatste redmiddel als zelfs de layout niet kan worden getoond (bijv. database weg).
            error_log((string) $renderError);
            header('Content-Type: text/plain; charset=utf-8');
            echo $status . ' - ' . $message;
        }
    }
}
