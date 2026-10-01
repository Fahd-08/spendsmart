<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Meldingen voor de gebruiker (succes, fout, waarschuwing, info) die op de
 * eerstvolgende pagina worden getoond, ook na een redirect.
 */
final class Flash
{
    public const TYPES = ['success', 'error', 'warning', 'info'];

    private const SESSION_KEY = '_flash_messages';

    public static function add(string $type, string $text): void
    {
        $type = in_array($type, self::TYPES, true) ? $type : 'info';

        $messages = Session::get(self::SESSION_KEY, []);
        $messages[] = ['type' => $type, 'text' => $text];
        Session::set(self::SESSION_KEY, $messages);
    }

    /**
     * @return array<int, array{type: string, text: string}>
     */
    public static function consume(): array
    {
        $messages = Session::get(self::SESSION_KEY, []);
        Session::set(self::SESSION_KEY, []);

        return $messages;
    }
}
