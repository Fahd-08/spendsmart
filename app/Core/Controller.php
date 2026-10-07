<?php

declare(strict_types=1);

namespace App\Core;

use App\Support\Month;
use PDO;

/**
 * Basisklasse voor alle controllers. Bevat hulpmethodes die elke controller nodig heeft,
 * zodat die code niet in elke controller opnieuw staat.
 */
abstract class Controller
{
    /**
     * Elke controller krijgt het verzoek mee (URL, formulierinvoer).
     */
    public function __construct(protected readonly Request $request)
    {
    }

    /**
     * Toont een pagina: rendert de template met de layout eromheen.
     *
     * @param string $template bijv. 'transactions/index' (= app/Views/transactions/index.php)
     * @param array  $data     variabelen voor de template, bijv. ['transactions' => [...]]
     * @param int    $status   HTTP-status, bijv. 422 als het formulier fouten bevat
     */
    protected function view(string $template, array $data = [], int $status = 200): void
    {
        http_response_code($status);
        echo View::render($template, $data);
    }

    /**
     * Stuurt door naar een andere pagina binnen de app, bijv. redirect('/goals').
     */
    protected function redirect(string $path, array $query = []): never
    {
        Response::redirect(url($path, $query));
    }

    /**
     * De databaseverbinding, voor het maken van repositories.
     */
    protected function db(): PDO
    {
        return Database::connection();
    }

    /**
     * ID van de ingelogde gebruiker. Routes met deze aanroep hebben altijd de middleware 'auth'.
     */
    protected function userId(): int
    {
        return Auth::id() ?? throw new HttpException(401, 'Log in om verder te gaan.');
    }

    /**
     * Toont de 404-pagina, bijv. als iemand een transactie van een ander probeert te openen.
     */
    protected function notFound(string $message): never
    {
        throw new HttpException(404, $message);
    }

    /**
     * Ingevulde formulierwaarden (altijd strings), om een formulier opnieuw te tonen na een fout.
     * Zo hoeft de gebruiker niet alles opnieuw in te typen.
     *
     * @param string[] $fields
     */
    protected function formValues(array $fields): array
    {
        $values = [];
        foreach ($fields as $field) {
            $values[$field] = $this->request->input($field);
        }

        return $values;
    }

    /**
     * Gekozen maand uit ?month=YYYY-MM, of de huidige maand.
     */
    protected function selectedMonth(): Month
    {
        $value = $this->request->query('month');
        $month = Month::tryFromString($value);

        // Iets ingevuld wat geen geldige maand is (bijv. 2026-13)? Melding tonen.
        if ($month === null && $value !== '') {
            Flash::add('info', 'De gekozen maand is ongeldig. De huidige maand wordt getoond.');
        }

        return $month ?? Month::current();
    }
}
