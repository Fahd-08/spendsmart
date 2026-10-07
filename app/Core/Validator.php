<?php

declare(strict_types=1);

namespace App\Core;

use App\Support\Money;
use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Controleert formulierinvoer met regels zoals 'required|max:100|email'
 * en geeft per veld een begrijpelijke Nederlandse foutmelding.
 *
 * Regels: required, max:n, min:n, email, integer, in:a,b, money (> 0), money_zero (>= 0),
 * date, password, confirmed, raw (niet trimmen).
 * Geldige bedragen worden omgezet naar centen (int), 'integer' naar int.
 *
 * Voorbeeld in een controller:
 *   $validator = Validator::make($this->request->body(), ['amount' => 'required|money'], ['amount' => 'Bedrag']);
 *   if ($validator->fails()) { ... toon $validator->errors() ... }
 *   $amount = $validator->validated()['amount']; // bijv. 1250 (centen)
 */
final class Validator
{
    /** Foutmeldingen per veld, bijv. ['amount' => 'Bedrag is verplicht.'] */
    private array $errors = [];

    /** Goedgekeurde en omgezette waarden per veld. */
    private array $validated = [];

    private function __construct(private readonly array $input, private readonly array $labels)
    {
    }

    /**
     * Maakt een validator en controleert meteen alle velden.
     *
     * @param array<string, string> $rules  veldnaam => regels
     * @param array<string, string> $labels veldnaam => naam zoals de gebruiker hem ziet
     */
    public static function make(array $input, array $rules, array $labels = []): self
    {
        $validator = new self($input, $labels);

        foreach ($rules as $field => $ruleString) {
            // 'required|max:100' wordt ['required', 'max:100'].
            $validator->validateField($field, explode('|', $ruleString));
        }

        return $validator;
    }

    /**
     * Zijn er fouten gevonden?
     */
    public function fails(): bool
    {
        return $this->errors !== [];
    }

    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * De goedgekeurde waarden (bedragen al in centen, getallen al als int).
     */
    public function validated(): array
    {
        return $this->validated;
    }

    /**
     * Extra controle vanuit de controller, bijvoorbeeld "e-mailadres is al in gebruik".
     * Een veld houdt altijd zijn eerste foutmelding.
     */
    public function addError(string $field, string $message): void
    {
        $this->errors[$field] ??= $message;
        unset($this->validated[$field]);
    }

    /**
     * Controleert één veld met al zijn regels. Bij de eerste fout stopt het.
     */
    private function validateField(string $field, array $rules): void
    {
        $raw = $this->input[$field] ?? '';

        // Wachtwoorden niet trimmen: een spatie aan het begin of eind hoort bij het wachtwoord.
        $keepRaw = in_array('raw', $rules, true) || in_array('password', $rules, true);

        // Alleen tekst accepteren; een array telt als leeg.
        $value = is_string($raw) ? ($keepRaw ? $raw : trim($raw)) : '';

        // Naam van het veld zoals de gebruiker het kent ('Bedrag' in plaats van 'amount').
        $label = $this->labels[$field] ?? $field;

        // Leeg veld: fout als het verplicht is, anders wordt de waarde null.
        if ($value === '') {
            if (in_array('required', $rules, true)) {
                $this->errors[$field] = "{$label} is verplicht.";
            } else {
                $this->validated[$field] = null;
            }

            return;
        }

        foreach ($rules as $rule) {
            // 'max:100' wordt naam 'max' en parameter '100'.
            [$name, $parameter] = array_pad(explode(':', $rule, 2), 2, '');
            $error = $this->check($name, $parameter, $value, $field, $label);

            if ($error !== null) {
                $this->errors[$field] = $error;

                return;
            }
        }

        // Alle regels goed: waarde omzetten (bijv. '12,50' naar 1250) en bewaren.
        $this->validated[$field] = $this->convert($value, $rules);
    }

    /**
     * Controleert één regel. Geeft een foutmelding, of null als het goed is.
     */
    private function check(string $rule, string $parameter, string $value, string $field, string $label): ?string
    {
        return match ($rule) {
            // 'required' is al gecontroleerd; 'raw' is geen controle maar een instelling.
            'required', 'raw' => null,

            // Maximaal aantal tekens (mb_strlen telt 'é' als één teken).
            'max' => mb_strlen($value) > (int) $parameter
                ? "{$label} mag maximaal {$parameter} tekens bevatten."
                : null,

            // Minimaal aantal tekens.
            'min' => mb_strlen($value) < (int) $parameter
                ? "{$label} moet minimaal {$parameter} tekens bevatten."
                : null,

            // Geldig e-mailadres volgens de ingebouwde PHP-controle.
            'email' => filter_var($value, FILTER_VALIDATE_EMAIL) === false
                ? 'Vul een geldig e-mailadres in, bijvoorbeeld naam@voorbeeld.nl.'
                : null,

            // Alleen cijfers (dus positief) en niet te lang voor de database.
            'integer' => ctype_digit($value) && strlen($value) <= 10
                ? null
                : "Kies een geldige waarde bij {$label}.",

            // Waarde moet in de lijst staan, bijv. 'in:income,expense'.
            'in' => in_array($value, explode(',', $parameter), true)
                ? null
                : "Kies een geldige waarde bij {$label}.",

            // Bedrag groter dan 0, of groter dan of gelijk aan 0.
            'money' => self::checkMoney($value, $label, false),
            'money_zero' => self::checkMoney($value, $label, true),

            // Bestaande datum (dus geen 30 februari) tussen 2000 en 2100.
            'date' => self::isValidDate($value)
                ? null
                : "{$label} moet een geldige datum zijn (tussen 2000 en 2100).",

            // Sterk genoeg wachtwoord.
            'password' => self::isStrongPassword($value)
                ? null
                : 'Het wachtwoord moet minimaal 8 tekens hebben, met minstens één letter en één cijfer.',

            // Veld moet gelijk zijn aan het herhaalveld, bijv. password en password_confirmation.
            'confirmed' => $value === ($this->input[$field . '_confirmation'] ?? null)
                ? null
                : 'De twee ingevulde wachtwoorden komen niet overeen.',

            // Typfout in een regel is een fout van de programmeur, niet van de gebruiker.
            default => throw new InvalidArgumentException("Onbekende validatieregel: {$rule}"),
        };
    }

    /**
     * Zet een goedgekeurde waarde om naar het juiste type.
     */
    private function convert(string $value, array $rules): string|int
    {
        // Bedragen naar centen: '12,50' wordt 1250.
        if (in_array('money', $rules, true) || in_array('money_zero', $rules, true)) {
            return (int) Money::parse($value);
        }

        // Getal als tekst naar int: '7' wordt 7.
        if (in_array('integer', $rules, true)) {
            return (int) $value;
        }

        return $value;
    }

    /**
     * Controleert een bedrag met de Money-klasse.
     */
    private static function checkMoney(string $value, string $label, bool $allowZero): ?string
    {
        $cents = Money::parse($value);

        if ($cents === null) {
            return "{$label} moet een bedrag zijn, bijvoorbeeld 12,50 (maximaal 2 cijfers achter de komma en niet hoger dan 9999999,99).";
        }

        if (!$allowZero && $cents === 0) {
            return "{$label} moet groter zijn dan € 0,00.";
        }

        return null;
    }

    /**
     * Bestaat deze datum echt (JJJJ-MM-DD) en ligt hij tussen 2000 en 2100?
     */
    public static function isValidDate(string $value): bool
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        // PHP maakt van 2026-02-30 stilletjes 2026-03-02; door terug te vergelijken vangen we dat af.
        if ($date === false || $date->format('Y-m-d') !== $value) {
            return false;
        }

        $year = (int) $date->format('Y');

        return $year >= 2000 && $year <= 2100;
    }

    /**
     * Minimaal 8 en maximaal 255 tekens, met minstens één letter en één cijfer.
     */
    public static function isStrongPassword(string $value): bool
    {
        return mb_strlen($value) >= 8
            && mb_strlen($value) <= 255
            && preg_match('/\pL/u', $value) === 1   // \pL = een letter in elke taal (ook é)
            && preg_match('/\d/', $value) === 1;    // \d = een cijfer
    }
}
