<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Een categorie is een inkomsten- of een uitgavencategorie.
 * Het type van een transactie volgt uit de categorie, zodat die nooit tegenstrijdig zijn.
 * (Een uitgave in de categorie "Bijbaan" kan dus niet bestaan.)
 */
final class CategoryType
{
    public const INCOME = 'income';
    public const EXPENSE = 'expense';

    /** Beide soorten, bijv. om te controleren of een filter geldig is. */
    public const ALL = [self::INCOME, self::EXPENSE];

    /**
     * 'Inkomst' of 'Uitgave' (enkelvoud).
     */
    public static function label(string $type): string
    {
        return $type === self::INCOME ? 'Inkomst' : 'Uitgave';
    }

    /**
     * 'Inkomsten' of 'Uitgaven' (meervoud, voor kopjes).
     */
    public static function pluralLabel(string $type): string
    {
        return $type === self::INCOME ? 'Inkomsten' : 'Uitgaven';
    }

    /**
     * Validatieregel 'in:income,expense' voor de Validator.
     */
    public static function ruleIn(): string
    {
        return 'in:' . implode(',', self::ALL);
    }
}
