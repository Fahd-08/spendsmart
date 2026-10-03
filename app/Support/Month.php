<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Een kalendermaand, gebruikt voor het maandfilter en de maandtotalen.
 */
final class Month
{
    private const NAMES = [
        1 => 'januari', 'februari', 'maart', 'april', 'mei', 'juni',
        'juli', 'augustus', 'september', 'oktober', 'november', 'december',
    ];

    private function __construct(public readonly int $year, public readonly int $month)
    {
    }

    public static function current(): self
    {
        return self::fromDate(date('Y-m-d'));
    }

    public static function fromDate(string $date): self
    {
        return new self((int) substr($date, 0, 4), (int) substr($date, 5, 2));
    }

    /**
     * "2026-09" => Month, ongeldige waarde => null.
     */
    public static function tryFromString(string $value): ?self
    {
        if (!preg_match('/^(\d{4})-(\d{2})$/', $value, $matches)) {
            return null;
        }

        $year = (int) $matches[1];
        $month = (int) $matches[2];

        if ($year < 2000 || $year > 2100 || $month < 1 || $month > 12) {
            return null;
        }

        return new self($year, $month);
    }

    public function key(): string
    {
        return sprintf('%04d-%02d', $this->year, $this->month);
    }

    /** Eerste dag van de maand (inclusief). */
    public function start(): string
    {
        return $this->key() . '-01';
    }

    /** Eerste dag van de volgende maand (exclusief). */
    public function end(): string
    {
        return $this->next()->start();
    }

    public function label(): string
    {
        return self::NAMES[$this->month] . ' ' . $this->year;
    }

    public function shortLabel(): string
    {
        return substr(self::NAMES[$this->month], 0, 3) . ' ' . $this->year;
    }

    public function previous(): self
    {
        return $this->month === 1 ? new self($this->year - 1, 12) : new self($this->year, $this->month - 1);
    }

    public function next(): self
    {
        return $this->month === 12 ? new self($this->year + 1, 1) : new self($this->year, $this->month + 1);
    }

    public function isCurrent(): bool
    {
        return $this->key() === self::current()->key();
    }

    public function contains(string $date): bool
    {
        return $date >= $this->start() && $date < $this->end();
    }
}
