<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Support\Month;
use Tests\FeatureTestCase;

/**
 * FE-04: Inkomst of uitgave met datum en categorie registreren, wijzigen en verwijderen.
 *
 * Leeswijzer: elke test heeft drie stappen: klaarzetten (bijv. actingAs = inloggen),
 * actie (get/post = pagina openen of formulier versturen) en controleren (assert...).
 * test_... = normaal gebruik, test_unhappy_... = foute invoer of geen toegang, test_randgeval_... = grensgeval.
 *
 * @group FE-04
 */
final class TransactionTest extends FeatureTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(self::SAM_ID);
    }

    public function test_formulier_toont_eigen_categorieen(): void
    {
        $this->get('/transactions/create')
            ->assertOk()
            ->assertSee('Boodschappen')
            ->assertDontSee('Huur');
    }

    public function test_gebruiker_registreert_een_uitgave(): void
    {
        $response = $this->post('/transactions', [
            'category_id' => (string) self::SAM_CADEAUS,
            'amount' => '12,50',
            'transaction_date' => '2026-03-15',
            'description' => 'Cadeau moeder',
        ]);

        $response->assertRedirect('/transactions?month=2026-03');
        $this->assertFlash('success', 'Uitgave van € 12,50 is opgeslagen.');

        $row = $this->row('SELECT user_id, category_id, amount_cents, transaction_date FROM transactions WHERE description = ?', ['Cadeau moeder']);
        $this->assertSame(
            ['user_id' => self::SAM_ID, 'category_id' => self::SAM_CADEAUS, 'amount_cents' => 1250, 'transaction_date' => '2026-03-15'],
            $row
        );

        $this->follow($response)->assertSee('Cadeau moeder')->assertSee('€ 12,50');
    }

    public function test_gebruiker_registreert_een_inkomst_zonder_omschrijving(): void
    {
        $this->post('/transactions', [
            'category_id' => (string) self::SAM_BIJBAAN,
            'amount' => '100',
            'transaction_date' => '2026-03-01',
        ])->assertRedirect('/transactions?month=2026-03');

        $this->assertFlash('success', 'Inkomst van € 100,00 is opgeslagen.');
        $this->assertNull($this->value("SELECT description FROM transactions WHERE transaction_date = '2026-03-01'"));
    }

    public function test_gebruiker_wijzigt_een_transactie(): void
    {
        $id = $this->samTransactionId('OV-saldo opgeladen');

        $this->get("/transactions/{$id}/edit")->assertOk()->assertSee('36,00');

        $this->post("/transactions/{$id}/update", [
            'category_id' => (string) self::SAM_VERVOER,
            'amount' => '40',
            'transaction_date' => '2026-04-02',
            'description' => 'OV-saldo aangepast',
        ])->assertRedirect('/transactions?month=2026-04');

        $this->assertSame(
            ['amount_cents' => 4000, 'transaction_date' => '2026-04-02', 'description' => 'OV-saldo aangepast'],
            $this->row('SELECT amount_cents, transaction_date, description FROM transactions WHERE id = ?', [$id])
        );
    }

    public function test_gebruiker_verwijdert_een_transactie(): void
    {
        $id = $this->samTransactionId('Streamingdienst');

        $this->post("/transactions/{$id}/delete")->assertRedirect('/transactions?month=' . Month::current()->key());

        $this->assertFlash('success', 'De transactie is verwijderd.');
        $this->assertSame(0, $this->countRows('transactions', 'id = ?', [$id]));
    }

    public function test_unhappy_ongeldig_bedrag_en_ongeldige_datum(): void
    {
        $count = $this->countRows('transactions');

        $this->post('/transactions', [
            'category_id' => (string) self::SAM_BOODSCHAPPEN,
            'amount' => '12,345',
            'transaction_date' => '2026-02-30',
        ])->assertStatus(422)
            ->assertSee('Bedrag moet een bedrag zijn')
            ->assertSee('Datum moet een geldige datum zijn');

        $this->assertSame($count, $this->countRows('transactions'));
    }

    public function test_unhappy_bedrag_van_nul(): void
    {
        $this->post('/transactions', [
            'category_id' => (string) self::SAM_BOODSCHAPPEN,
            'amount' => '0',
            'transaction_date' => '2026-03-01',
        ])->assertStatus(422)->assertSee('Bedrag moet groter zijn dan € 0,00.');
    }

    public function test_unhappy_categorie_van_een_andere_gebruiker(): void
    {
        $this->post('/transactions', [
            'category_id' => (string) self::SANNE_HUUR,
            'amount' => '10',
            'transaction_date' => '2026-03-01',
        ])->assertStatus(422)->assertSee('Kies een van je eigen categorieën.');

        $this->assertSame(1, $this->countRows('transactions', 'category_id = ?', [self::SANNE_HUUR]));
    }

    public function test_randgeval_hoogst_toegestane_bedrag(): void
    {
        $this->post('/transactions', [
            'category_id' => (string) self::SAM_BIJBAAN,
            'amount' => '9999999,99',
            'transaction_date' => '2026-03-01',
        ])->assertRedirect('/transactions?month=2026-03');

        $this->post('/transactions', [
            'category_id' => (string) self::SAM_BIJBAAN,
            'amount' => '10000000',
            'transaction_date' => '2026-03-01',
        ])->assertStatus(422);

        $this->assertSame(1, $this->countRows('transactions', "transaction_date = '2026-03-01'"));
    }

    public function test_randgeval_omschrijving_van_255_tekens_wel_256_niet(): void
    {
        $data = ['category_id' => (string) self::SAM_CADEAUS, 'amount' => '1', 'transaction_date' => '2026-03-01'];

        $this->post('/transactions', $data + ['description' => str_repeat('x', 256)])
            ->assertStatus(422)
            ->assertSee('Omschrijving mag maximaal 255 tekens bevatten.');

        $this->post('/transactions', $data + ['description' => str_repeat('x', 255)])->assertRedirect('/transactions?month=2026-03');
    }

    public function test_randgeval_html_in_omschrijving_wordt_als_tekst_getoond(): void
    {
        $this->post('/transactions', [
            'category_id' => (string) self::SAM_CADEAUS,
            'amount' => '5',
            'transaction_date' => '2026-03-01',
            'description' => '<script>alert(1)</script>',
        ]);

        $this->get('/transactions?month=2026-03')
            ->assertSee('<script>alert(1)</script>')
            ->assertDontSeeRaw('<script>alert(1)</script>');
    }

    public function test_unhappy_transactie_van_een_ander_bekijken_of_verwijderen(): void
    {
        $sanneTransaction = (int) $this->value('SELECT id FROM transactions WHERE user_id = ? LIMIT 1', [self::SANNE_ID]);

        $this->get("/transactions/{$sanneTransaction}/edit")->assertStatus(404);
        $this->post("/transactions/{$sanneTransaction}/delete")->assertStatus(404);
        $this->post("/transactions/{$sanneTransaction}/update", [
            'category_id' => (string) self::SAM_CADEAUS, 'amount' => '1', 'transaction_date' => '2026-03-01',
        ])->assertStatus(404);

        $this->assertSame(1, $this->countRows('transactions', 'id = ?', [$sanneTransaction]));
    }

    public function test_unhappy_wijzigen_met_ongeldige_invoer(): void
    {
        $id = $this->samTransactionId('OV-saldo opgeladen');

        $this->post("/transactions/{$id}/update", ['category_id' => 'abc', 'amount' => '', 'transaction_date' => ''])
            ->assertStatus(422)
            ->assertSee('Kies een geldige waarde bij Categorie.')
            ->assertSee('Bedrag is verplicht.');

        $this->assertSame(3600, (int) $this->value('SELECT amount_cents FROM transactions WHERE id = ?', [$id]));
    }

    public function test_randgeval_zonder_categorieen_eerst_een_categorie_maken(): void
    {
        // Klaarzetten: de database direct aanpassen om dit scenario na te bootsen.
        $this->db()->exec('DELETE FROM transactions WHERE user_id = ' . self::SAM_ID);
        $this->db()->exec('DELETE FROM categories WHERE user_id = ' . self::SAM_ID);

        $this->get('/transactions/create')->assertRedirect('/categories/create');
        $this->assertFlash('info', 'Maak eerst een categorie aan.');
    }

    private function samTransactionId(string $description): int
    {
        return (int) $this->value('SELECT id FROM transactions WHERE user_id = ? AND description = ?', [self::SAM_ID, $description]);
    }
}
