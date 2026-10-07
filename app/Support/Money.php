<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Bedragen worden overal als hele centen (int) opgeslagen en berekend.
 * Zo ontstaan geen afrondingsfouten zoals bij float (0.1 + 0.2 != 0.3).
 *
 * Voorbeeld: € 12,50 wordt opgeslagen als 1250.
 */
final class Money
{
    /** Hoogste toegestane bedrag: € 9.999.999,99 (999.999.999 centen). */
    public const MAX_CENTS = 999_999_999;

    /**
     * Zet invoer als "12,50", "12.5", "€ 12" om naar centen. Geeft null bij ongeldige invoer.
     * Duizendtalscheidingstekens zijn niet toegestaan (voorkomt verwarring tussen 1.250 en 1,250).
     */
    public static function parse(string $input): ?int
    {
        // Euroteken en spaties weghalen: ' € 12,50 ' -> '12,50'.
        $value = str_replace(['€', ' '], '', trim($input));

        // Patroon: 1 tot 7 cijfers, optioneel een komma of punt met 1 of 2 cijfers.
        // Groep 1 = de euro's, groep 2 = de centen.
        if (!preg_match('/^(\d{1,7})(?:[.,](\d{1,2}))?$/', $value, $matches)) {
            return null;
        }

        $euros = (int) $matches[1];

        // '5' achter de komma betekent 50 cent, dus aanvullen met een 0: '5' -> '50'.
        $cents = isset($matches[2]) ? (int) str_pad($matches[2], 2, '0') : 0;

        return $euros * 100 + $cents;
    }

    /**
     * 123456 => "€ 1.234,56", -500 => "- € 5,00"
     */
    public static function format(int $cents): string
    {
        $sign = $cents < 0 ? '- ' : '';
        $absolute = abs($cents);

        // intdiv = hele euro's (met punt per duizend), % 100 = de centen (altijd 2 cijfers).
        return $sign . '€ ' . number_format(intdiv($absolute, 100), 0, ',', '.')
            . ',' . str_pad((string) ($absolute % 100), 2, '0', STR_PAD_LEFT);
    }

    /**
     * Waarde voor een invoerveld: 1250 => "12,50"
     */
    public static function toInput(int $cents): string
    {
        return intdiv($cents, 100) . ',' . str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
    }

    /**
     * Percentage (afgerond naar beneden) zonder floats.
     * Voorbeeld: 50 van 200 = 25%.
     */
    public static function percentage(int $part, int $total): int
    {
        // Niet delen door 0.
        if ($total <= 0) {
            return 0;
        }

        return intdiv($part * 100, $total);
    }
}
