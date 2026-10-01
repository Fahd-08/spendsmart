<?php

declare(strict_types=1);

namespace App\Core;

use App\Support\Month;
use PDO;

/**
 * Basisklasse voor alle controllers.
 */
abstract class Controller
{
    public function __construct(protected readonly Request $request)
    {
    }

    protected function view(string $template, array $data = [], int $status = 200): void
    {
        http_response_code($status);
        echo View::render($template, $data);
    }

    protected function redirect(string $path, array $query = []): never
    {
        Response::redirect(url($path, $query));
    }

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

    protected function notFound(string $message): never
    {
        throw new HttpException(404, $message);
    }

    /**
     * Ingevulde formulierwaarden (altijd strings), om een formulier opnieuw te tonen na een fout.
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

        if ($month === null && $value !== '') {
            Flash::add('info', 'De gekozen maand is ongeldig. De huidige maand wordt getoond.');
        }

        return $month ?? Month::current();
    }
}
