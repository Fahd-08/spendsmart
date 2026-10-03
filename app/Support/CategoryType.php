<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Een categorie is een inkomsten- of een uitgavencategorie.
 * Het type van een transactie volgt uit de categorie, zodat die nooit tegenstrijdig zijn.
 */
final class CategoryType
{
    public const INCOME = 'income';
    public const EXPENSE = 'expense';

    public const ALL = [self::INCOME, self::EXPENSE];

    public static function label(string $type): string
    {
        return $type === self::INCOME ? 'Inkomst' : 'Uitgave';
    }

    public static function pluralLabel(string $type): string
    {
        return $type === self::INCOME ? 'Inkomsten' : 'Uitgaven';
    }

    public static function ruleIn(): string
    {
        return 'in:' . implode(',', self::ALL);
    }
}
