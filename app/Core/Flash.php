<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Meldingen voor de gebruiker (succes, fout, waarschuwing, info) die op de
 * eerstvolgende pagina worden getoond, ook na een redirect.
 *
 * Voorbeeld: na opslaan staat "Uitgave van € 12,50 is opgeslagen." bovenaan de volgende pagina.
 */
final class Flash
{
    /** De vier soorten meldingen; elk heeft een eigen kleur en icoon in de CSS. */
    public const TYPES = ['success', 'error', 'warning', 'info'];

    private const SESSION_KEY = '_flash_messages';

    /**
     * Zet een melding klaar in de sessie.
     */
    public static function add(string $type, string $text): void
    {
        // Onbekende soort? Dan wordt het een info-melding.
        $type = in_array($type, self::TYPES, true) ? $type : 'info';

        $messages = Session::get(self::SESSION_KEY, []);
        $messages[] = ['type' => $type, 'text' => $text];
        Session::set(self::SESSION_KEY, $messages);
    }

    /**
     * Geeft alle klaargezette meldingen en wist ze, zodat ze maar één keer worden getoond.
     * Wordt aangeroepen in de layout (app/Views/layouts/main.php).
     *
     * @return array<int, array{type: string, text: string}>
     */
    public static function consume(): array
    {
        $messages = Session::get(self::SESSION_KEY, []);
        Session::set(self::SESSION_KEY, []);

        // Belangrijkste meldingen eerst: fouten en waarschuwingen boven "gelukt" en info,
        // zodat een waarschuwing (bijv. limiet overschreden) niet onder een andere melding verdwijnt.
        $order = array_flip(['error', 'warning', 'success', 'info']);
        usort($messages, static fn (array $a, array $b): int => $order[$a['type']] <=> $order[$b['type']]);

        return $messages;
    }
}
