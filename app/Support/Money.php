<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Bedragen worden overal als hele centen (int) opgeslagen en berekend.
 * Zo ontstaan geen afrondingsfouten zoals bij float (0.1 + 0.2 != 0.3).
 */
final class Money
{
    /** € 9.999.999,99 */
    public const MAX_CENTS = 999_999_999;

    /**
     * Zet invoer als "12,50", "12.5", "€ 12" om naar centen. Geeft null bij ongeldige invoer.
     * Duizendtalscheidingstekens zijn niet toegestaan (voorkomt verwarring tussen 1.250 en 1,250).
     */
    public static function parse(string $input): ?int
    {
        $value = str_replace(['€', ' '], '', trim($input));

        if (!preg_match('/^(\d{1,7})(?:[.,](\d{1,2}))?$/', $value, $matches)) {
            return null;
        }

        $euros = (int) $matches[1];
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
     */
    public static function percentage(int $part, int $total): int
    {
        if ($total <= 0) {
            return 0;
        }

        return intdiv($part * 100, $total);
    }
}
