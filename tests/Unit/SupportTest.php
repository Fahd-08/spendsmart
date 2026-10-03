<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Env;
use App\Core\ErrorHandler;
use App\Core\HttpException;
use App\Support\CategoryType;
use App\Support\Role;
use PDOException;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Kleine hulpfuncties en -klassen die overal in de schermen worden gebruikt.
 */
final class SupportTest extends TestCase
{
    public function test_html_wordt_veilig_gemaakt_tegen_xss(): void
    {
        $this->assertSame('&lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt;', e('<script>alert("x")</script>'));
        $this->assertSame('Sam&#039;s', e("Sam's"));
    }

    public function test_voortgangsbalk_blijft_tussen_0_en_100_procent(): void
    {
        $this->assertSame(40, bar_width(42));
        $this->assertSame(45, bar_width(43));
        $this->assertSame(0, bar_width(-5));
        $this->assertSame(100, bar_width(250));
    }

    public function test_datum_wordt_in_nederlandse_notatie_getoond(): void
    {
        $this->assertSame('30-09-2026', format_date('2026-09-30'));
        $this->assertSame('30-09-2026', format_date('2026-09-30 14:00:00'));
        $this->assertSame('', format_date(null));
        $this->assertSame('onbekend', format_date('onbekend'));
    }

    public function test_url_met_filters(): void
    {
        $this->assertSame('/transactions?month=2026-09&type=expense', url('/transactions', ['month' => '2026-09', 'type' => 'expense', 'category' => '']));
    }

    public function test_veldfout_wordt_veilig_en_toegankelijk_getoond(): void
    {
        $errors = ['name' => 'Naam <b> is verplicht.'];

        $this->assertSame('<p class="field-error" id="name-error">Naam &lt;b&gt; is verplicht.</p>', field_error($errors, 'name'));
        $this->assertSame(' aria-invalid="true" aria-describedby="name-error"', field_attributes($errors, 'name'));
        $this->assertSame('', field_error($errors, 'email'));
        $this->assertSame('', field_attributes($errors, 'email'));
    }

    public function test_rollen_en_startpagina(): void
    {
        $this->assertSame('Gebruiker', Role::label(Role::USER));
        $this->assertSame('Contentbeheerder', Role::label(Role::CONTENT_MANAGER));
        $this->assertSame('Onbekend', Role::label('admin'));
        $this->assertSame('/dashboard', Role::homePath(Role::USER));
        $this->assertSame('/content/statistics', Role::homePath(Role::CONTENT_MANAGER));
        $this->assertSame('/', Role::homePath(''));
    }

    public function test_soort_categorie(): void
    {
        $this->assertSame('Inkomst', CategoryType::label(CategoryType::INCOME));
        $this->assertSame('Uitgaven', CategoryType::pluralLabel(CategoryType::EXPENSE));
        $this->assertSame('Inkomsten', CategoryType::pluralLabel(CategoryType::INCOME));
        $this->assertSame('in:income,expense', CategoryType::ruleIn());
    }

    public function test_env_bestand_overschrijft_geen_bestaande_omgevingsvariabele(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'env');
        file_put_contents($file, "# commentaar\nDB_NAME=echte_database\nSPENDSMART_TEST_KEY=\"waarde\"\nzonder_isgelijkteken\n");

        Env::load($file);
        unlink($file);

        $this->assertSame('spendsmart_test', Env::get('DB_NAME'));
        $this->assertSame('waarde', Env::get('SPENDSMART_TEST_KEY'));
        $this->assertSame('standaard', Env::get('BESTAAT_NIET', 'standaard'));
    }

    public function test_foutpagina_toont_geen_technische_details(): void
    {
        $output = $this->captureErrorPage(new RuntimeException('SQLSTATE geheim detail'));

        $this->assertStringContainsString('Er ging onverwacht iets mis', $output);
        $this->assertStringNotContainsString('geheim detail', $output);
        $this->assertSame(500, http_response_code());
    }

    public function test_foutpagina_bij_database_storing(): void
    {
        $output = $this->captureErrorPage(new PDOException('Connection refused'));

        $this->assertStringContainsString('De database is op dit moment niet bereikbaar', $output);
    }

    public function test_foutpagina_met_statuscode(): void
    {
        $output = $this->captureErrorPage(new HttpException(404, 'Deze pagina bestaat niet.'));

        $this->assertStringContainsString('Niet gevonden', $output);
        $this->assertSame(404, http_response_code());
    }

    private function captureErrorPage(\Throwable $exception): string
    {
        // Fouten worden gelogd; tijdens de test niet naar de terminal of het logbestand schrijven.
        $previousLog = ini_set('error_log', '/dev/null');

        ob_start();
        ErrorHandler::handle($exception);
        $output = (string) ob_get_clean();

        ini_set('error_log', (string) $previousLog);

        return $output;
    }
}
