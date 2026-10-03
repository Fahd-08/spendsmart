<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Repositories\CategorySuggestionRepository;
use App\Repositories\StatisticsRepository;
use App\Services\StatisticsService;
use App\Support\Month;
use Tests\FeatureTestCase;

/**
 * FE-12: Contentbeheerder ziet anonieme gebruiksaantallen (geen namen, e-mailadressen of bedragen).
 *
 * @group FE-12
 */
final class StatisticsTest extends FeatureTestCase
{
    public function test_aantallen_kloppen_met_de_database(): void
    {
        $overview = $this->service()->overview();

        $this->assertSame(2, $overview['user_count'], 'Alleen gewone gebruikers tellen mee, niet de contentbeheerder.');
        $this->assertSame(2, $overview['new_user_count']);
        $this->assertSame(18, $overview['transaction_count']);
        $this->assertSame(4, $overview['goal_count']);
        $this->assertSame(1, $overview['goals_reached_count']);
    }

    public function test_activiteit_van_de_laatste_6_maanden_inclusief_lege_maanden(): void
    {
        $activity = $this->service()->overview()['activity'];

        $this->assertCount(6, $activity);
        $this->assertSame(Month::current()->shortLabel(), $activity[5]['label']);
        $this->assertSame(['transaction_count' => 13, 'active_users' => 2, 'percentage' => 100], array_slice($activity[5], 1));
        $this->assertSame(5, $activity[4]['transaction_count']);
        $this->assertSame(0, $activity[0]['transaction_count'], 'Maand zonder activiteit telt als 0.');
    }

    public function test_contentbeheerder_ziet_statistieken_zonder_persoonsgegevens(): void
    {
        $this->actingAs(self::CONTENT_MANAGER_ID)->get('/content/statistics')
            ->assertOk()
            ->assertSee('Statistieken')
            ->assertSee('Gebruik van categorievoorstellen')
            ->assertDontSee('Sam Student')
            ->assertDontSee('student@spendsmart.test')
            ->assertDontSee('Weekboodschappen')
            ->assertDontSee('€');
    }

    public function test_gebruik_van_voorstellen_wordt_geteld(): void
    {
        $suggestions = array_column($this->service()->overview()['suggestions'], 'adoption_count', 'name');

        $this->assertSame(2, (int) $suggestions['Bijbaan']);
        $this->assertSame(0, (int) $suggestions['Studiekosten']);
    }

    public function test_randgeval_lege_database_geeft_overal_nul(): void
    {
        $this->db()->exec('DELETE FROM transactions');
        $this->db()->exec('DELETE FROM savings_goals');

        $overview = $this->service()->overview();

        $this->assertSame(0, $overview['transaction_count']);
        $this->assertSame(0, $overview['goal_count']);
        $this->assertSame(0, $overview['goals_reached_count']);
        $this->assertSame(0, array_sum(array_column($overview['activity'], 'percentage')));

        $this->actingAs(self::CONTENT_MANAGER_ID)->get('/content/statistics')
            ->assertSee('Er zijn de afgelopen maanden nog geen transacties geregistreerd.');
    }

    public function test_unhappy_gebruiker_heeft_geen_toegang(): void
    {
        $this->actingAs(self::SAM_ID)->get('/content/statistics')
            ->assertStatus(403)
            ->assertSee('Je hebt met jouw rol geen toegang tot deze pagina of actie.');
    }

    private function service(): StatisticsService
    {
        return new StatisticsService(new StatisticsRepository($this->db()), new CategorySuggestionRepository($this->db()));
    }
}
