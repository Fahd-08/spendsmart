<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Een kalendermaand, gebruikt voor het maandfilter en de maandtotalen.
 * Voorbeeld: Month::tryFromString('2026-09') is september 2026.
 */
final class Month
{
    /** Nederlandse maandnamen (index 1 = januari). */
    private const NAMES = [
        1 => 'januari', 'februari', 'maart', 'april', 'mei', 'juni',
        'juli', 'augustus', 'september', 'oktober', 'november', 'december',
    ];

    /**
     * Private: een Month maak je via current(), fromDate() of tryFromString(),
     * zodat er nooit een ongeldige maand (zoals maand 13) kan bestaan.
     */
    private function __construct(public readonly int $year, public readonly int $month)
    {
    }

    /**
     * De maand van vandaag.
     */
    public static function current(): self
    {
        return self::fromDate(date('Y-m-d'));
    }

    /**
     * De maand van een datum: '2026-09-15' -> september 2026.
     */
    public static function fromDate(string $date): self
    {
        return new self((int) substr($date, 0, 4), (int) substr($date, 5, 2));
    }

    /**
     * "2026-09" => Month, ongeldige waarde => null.
     */
    public static function tryFromString(string $value): ?self
    {
        // Precies 4 cijfers, streepje, 2 cijfers.
        if (!preg_match('/^(\d{4})-(\d{2})$/', $value, $matches)) {
            return null;
        }

        $year = (int) $matches[1];
        $month = (int) $matches[2];

        // Alleen redelijke jaren en maanden 1 t/m 12.
        if ($year < 2000 || $year > 2100 || $month < 1 || $month > 12) {
            return null;
        }

        return new self($year, $month);
    }

    /**
     * Sleutel voor in de URL: '2026-09'.
     */
    public function key(): string
    {
        return sprintf('%04d-%02d', $this->year, $this->month);
    }

    /** Eerste dag van de maand (inclusief). */
    public function start(): string
    {
        return $this->key() . '-01';
    }

    /**
     * Eerste dag van de volgende maand (exclusief).
     * In SQL: datum >= start() AND datum < end(). Zo hoeven we niet te weten of een maand 28, 30 of 31 dagen heeft.
     */
    public function end(): string
    {
        return $this->next()->start();
    }

    /**
     * 'september 2026'
     */
    public function label(): string
    {
        return self::NAMES[$this->month] . ' ' . $this->year;
    }

    /**
     * 'sep 2026' (voor de statistiekgrafiek)
     */
    public function shortLabel(): string
    {
        return substr(self::NAMES[$this->month], 0, 3) . ' ' . $this->year;
    }

    /**
     * Vorige maand. Januari -> december van het jaar ervoor.
     */
    public function previous(): self
    {
        return $this->month === 1 ? new self($this->year - 1, 12) : new self($this->year, $this->month - 1);
    }

    /**
     * Volgende maand. December -> januari van het jaar erna.
     */
    public function next(): self
    {
        return $this->month === 12 ? new self($this->year + 1, 1) : new self($this->year, $this->month + 1);
    }

    /**
     * Is dit de maand van vandaag?
     */
    public function isCurrent(): bool
    {
        return $this->key() === self::current()->key();
    }

    /**
     * Valt deze datum in deze maand?
     */
    public function contains(string $date): bool
    {
        return $date >= $this->start() && $date < $this->end();
    }
}
