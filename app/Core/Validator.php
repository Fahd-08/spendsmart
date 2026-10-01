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
 */
final class Validator
{
    private array $errors = [];
    private array $validated = [];

    private function __construct(private readonly array $input, private readonly array $labels)
    {
    }

    /**
     * @param array<string, string> $rules  veldnaam => regels
     * @param array<string, string> $labels veldnaam => naam zoals de gebruiker hem ziet
     */
    public static function make(array $input, array $rules, array $labels = []): self
    {
        $validator = new self($input, $labels);

        foreach ($rules as $field => $ruleString) {
            $validator->validateField($field, explode('|', $ruleString));
        }

        return $validator;
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function validated(): array
    {
        return $this->validated;
    }

    /**
     * Extra controle vanuit de controller, bijvoorbeeld "e-mailadres is al in gebruik".
     */
    public function addError(string $field, string $message): void
    {
        $this->errors[$field] ??= $message;
        unset($this->validated[$field]);
    }

    private function validateField(string $field, array $rules): void
    {
        $raw = $this->input[$field] ?? '';
        $keepRaw = in_array('raw', $rules, true) || in_array('password', $rules, true);
        $value = is_string($raw) ? ($keepRaw ? $raw : trim($raw)) : '';
        $label = $this->labels[$field] ?? $field;

        if ($value === '') {
            if (in_array('required', $rules, true)) {
                $this->errors[$field] = "{$label} is verplicht.";
            } else {
                $this->validated[$field] = null;
            }

            return;
        }

        foreach ($rules as $rule) {
            [$name, $parameter] = array_pad(explode(':', $rule, 2), 2, '');
            $error = $this->check($name, $parameter, $value, $field, $label);

            if ($error !== null) {
                $this->errors[$field] = $error;

                return;
            }
        }

        $this->validated[$field] = $this->convert($value, $rules);
    }

    private function check(string $rule, string $parameter, string $value, string $field, string $label): ?string
    {
        return match ($rule) {
            'required', 'raw' => null,
            'max' => mb_strlen($value) > (int) $parameter
                ? "{$label} mag maximaal {$parameter} tekens bevatten."
                : null,
            'min' => mb_strlen($value) < (int) $parameter
                ? "{$label} moet minimaal {$parameter} tekens bevatten."
                : null,
            'email' => filter_var($value, FILTER_VALIDATE_EMAIL) === false
                ? 'Vul een geldig e-mailadres in, bijvoorbeeld naam@voorbeeld.nl.'
                : null,
            'integer' => ctype_digit($value) && strlen($value) <= 10
                ? null
                : "Kies een geldige waarde bij {$label}.",
            'in' => in_array($value, explode(',', $parameter), true)
                ? null
                : "Kies een geldige waarde bij {$label}.",
            'money' => self::checkMoney($value, $label, false),
            'money_zero' => self::checkMoney($value, $label, true),
            'date' => self::isValidDate($value)
                ? null
                : "{$label} moet een geldige datum zijn (tussen 2000 en 2100).",
            'password' => self::isStrongPassword($value)
                ? null
                : 'Het wachtwoord moet minimaal 8 tekens hebben, met minstens één letter en één cijfer.',
            'confirmed' => $value === ($this->input[$field . '_confirmation'] ?? null)
                ? null
                : 'De twee ingevulde wachtwoorden komen niet overeen.',
            default => throw new InvalidArgumentException("Onbekende validatieregel: {$rule}"),
        };
    }

    private function convert(string $value, array $rules): string|int
    {
        if (in_array('money', $rules, true) || in_array('money_zero', $rules, true)) {
            return (int) Money::parse($value);
        }

        if (in_array('integer', $rules, true)) {
            return (int) $value;
        }

        return $value;
    }

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

    public static function isValidDate(string $value): bool
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        if ($date === false || $date->format('Y-m-d') !== $value) {
            return false;
        }

        $year = (int) $date->format('Y');

        return $year >= 2000 && $year <= 2100;
    }

    public static function isStrongPassword(string $value): bool
    {
        return mb_strlen($value) >= 8
            && mb_strlen($value) <= 255
            && preg_match('/\pL/u', $value) === 1
            && preg_match('/\d/', $value) === 1;
    }
}
