<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Validator;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Alle formulierinvoer gaat door de Validator (TE-05). Elke regel krijgt een geldige en een ongeldige test.
 */
final class ValidatorTest extends TestCase
{
    public function test_geldige_invoer_wordt_opgeschoond_en_omgezet(): void
    {
        $validator = Validator::make(
            ['name' => '  Sam  ', 'amount' => '12,50', 'category_id' => '7', 'date' => '2026-09-30'],
            ['name' => 'required|max:100', 'amount' => 'required|money', 'category_id' => 'required|integer', 'date' => 'date']
        );

        $this->assertFalse($validator->fails());
        $this->assertSame(
            ['name' => 'Sam', 'amount' => 1250, 'category_id' => 7, 'date' => '2026-09-30'],
            $validator->validated()
        );
    }

    public function test_verplicht_veld_ontbreekt(): void
    {
        $validator = Validator::make(['name' => '   '], ['name' => 'required'], ['name' => 'Naam']);

        $this->assertTrue($validator->fails());
        $this->assertSame(['name' => 'Naam is verplicht.'], $validator->errors());
    }

    public function test_leeg_optioneel_veld_wordt_null(): void
    {
        $validator = Validator::make([], ['description' => 'max:255']);

        $this->assertFalse($validator->fails());
        $this->assertSame(['description' => null], $validator->validated());
    }

    public function test_randgeval_array_als_invoer_wordt_als_leeg_behandeld(): void
    {
        $validator = Validator::make(['name' => ['a', 'b']], ['name' => 'required'], ['name' => 'Naam']);

        $this->assertSame('Naam is verplicht.', $validator->errors()['name']);
    }

    public function test_randgeval_maximale_lengte_precies_op_de_grens(): void
    {
        $this->assertFalse(Validator::make(['n' => str_repeat('é', 60)], ['n' => 'max:60'])->fails());
        $this->assertTrue(Validator::make(['n' => str_repeat('é', 61)], ['n' => 'max:60'])->fails());
    }

    public function test_minimale_lengte(): void
    {
        $validator = Validator::make(['body' => 'kort'], ['body' => 'min:20'], ['body' => 'Tekst']);

        $this->assertSame('Tekst moet minimaal 20 tekens bevatten.', $validator->errors()['body']);
    }

    public function test_ongeldig_emailadres(): void
    {
        $validator = Validator::make(['email' => 'sam@'], ['email' => 'email']);

        $this->assertStringContainsString('geldig e-mailadres', $validator->errors()['email']);
    }

    /**
     * @dataProvider invalidIntegers
     */
    public function test_ongeldig_geheel_getal(string $value): void
    {
        $this->assertTrue(Validator::make(['id' => $value], ['id' => 'integer'])->fails());
    }

    public function invalidIntegers(): array
    {
        return [
            'negatief' => ['-1'],
            'decimaal' => ['1.5'],
            'tekst' => ['abc'],
            'randgeval: 11 cijfers' => ['12345678901'],
        ];
    }

    public function test_waarde_moet_in_de_lijst_staan(): void
    {
        $this->assertFalse(Validator::make(['type' => 'income'], ['type' => 'in:income,expense'])->fails());
        $this->assertTrue(Validator::make(['type' => 'savings'], ['type' => 'in:income,expense'])->fails());
    }

    public function test_bedrag_van_nul_is_niet_toegestaan_bij_money(): void
    {
        $validator = Validator::make(['amount' => '0,00'], ['amount' => 'money'], ['amount' => 'Bedrag']);

        $this->assertSame('Bedrag moet groter zijn dan € 0,00.', $validator->errors()['amount']);
    }

    public function test_randgeval_bedrag_van_nul_is_wel_toegestaan_bij_money_zero(): void
    {
        $validator = Validator::make(['limit' => '0'], ['limit' => 'money_zero']);

        $this->assertFalse($validator->fails());
        $this->assertSame(0, $validator->validated()['limit']);
    }

    public function test_ongeldig_bedrag(): void
    {
        $validator = Validator::make(['amount' => '12,345'], ['amount' => 'money'], ['amount' => 'Bedrag']);

        $this->assertStringStartsWith('Bedrag moet een bedrag zijn', $validator->errors()['amount']);
    }

    /**
     * @dataProvider invalidDates
     */
    public function test_ongeldige_datum(string $date): void
    {
        $this->assertFalse(Validator::isValidDate($date));
    }

    public function invalidDates(): array
    {
        return [
            'randgeval: 30 februari' => ['2026-02-30'],
            'randgeval: 29 februari in geen schrikkeljaar' => ['2027-02-29'],
            'randgeval: jaar 1999' => ['1999-12-31'],
            'verkeerde notatie' => ['30-09-2026'],
            'tekst' => ['gisteren'],
        ];
    }

    public function test_randgeval_29_februari_in_schrikkeljaar_is_geldig(): void
    {
        $this->assertTrue(Validator::isValidDate('2028-02-29'));
    }

    /**
     * @dataProvider weakPasswords
     */
    public function test_zwak_wachtwoord_wordt_geweigerd(string $password): void
    {
        $this->assertFalse(Validator::isStrongPassword($password));
    }

    public function weakPasswords(): array
    {
        return [
            'zonder cijfer' => ['wachtwoord'],
            'zonder letter' => ['12345678'],
            'randgeval: 7 tekens' => ['abcdef1'],
            'randgeval: 256 tekens' => [str_repeat('a', 255) . '1'],
        ];
    }

    public function test_randgeval_wachtwoord_van_precies_8_tekens_is_sterk_genoeg(): void
    {
        $this->assertTrue(Validator::isStrongPassword('abcdefg1'));
    }

    public function test_wachtwoord_wordt_niet_getrimd(): void
    {
        $validator = Validator::make(['password' => ' Welkom123! '], ['password' => 'password']);

        $this->assertSame(' Welkom123! ', $validator->validated()['password']);
    }

    public function test_herhaald_wachtwoord_moet_gelijk_zijn(): void
    {
        $rules = ['password' => 'password|confirmed'];

        $this->assertFalse(Validator::make(['password' => 'Welkom123!', 'password_confirmation' => 'Welkom123!'], $rules)->fails());
        $this->assertSame(
            'De twee ingevulde wachtwoorden komen niet overeen.',
            Validator::make(['password' => 'Welkom123!', 'password_confirmation' => 'Welkom124!'], $rules)->errors()['password']
        );
    }

    public function test_extra_fout_uit_controller_vervangt_geen_eerdere_fout(): void
    {
        $validator = Validator::make(['email' => 'sam@test.nl'], ['email' => 'email']);
        $validator->addError('email', 'Dit e-mailadres is al in gebruik.');
        $validator->addError('email', 'Tweede melding.');

        $this->assertSame(['email' => 'Dit e-mailadres is al in gebruik.'], $validator->errors());
        $this->assertArrayNotHasKey('email', $validator->validated());
    }

    public function test_onbekende_regel_is_een_programmeerfout(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Validator::make(['name' => 'Sam'], ['name' => 'bestaatniet']);
    }
}
