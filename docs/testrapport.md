# Testrapport SpendSmart (KT1-W4)

| | |
|---|---|
| Student | Fahd El Fechka (2214593, klas 24A) |
| Project | SpendSmart – budgettool (MVP) voor MoneyMinds |
| Testdatum | 1 oktober 2026 (bijgewerkt 7 oktober 2026, versie v1.5) |
| Testomgeving | macOS, XAMPP: PHP 8.2.4, MariaDB 10.4.28 |
| Testframework | PHPUnit 9.6, coverage met PCOV 1.0.12 |
| Resultaat | **238 tests, 565 controles (assertions): alle geslaagd** |
| Codedekking | **96,41 % van de regels**, 95,71 % van de methodes |
| Dekking functionele eisen | **12 van 12 (100 %)** |

## 1. Doel

Met deze tests laat ik zien dat alle functionele eisen uit KT1-W3 (FE-01 t/m FE-12) werken zoals bedoeld, ook als de gebruiker iets verkeerd invult (unhappy flows) en bij grensgevallen (randgevallen). Daarnaast controleren de tests dat frontend (HTML-pagina's en formulieren), backend (router, controllers, services) en database goed samenwerken.

## 2. Testaanpak

### 2.1 Twee soorten tests

| Soort | Map | Aantal | Wat wordt getest |
|---|---|---|---|
| Unittests | `tests/Unit` | 91 | Losse klassen zonder database: `Money` (bedragen in centen), `Month` (maandfilter), `Validator` (alle invoerregels), `BudgetService::status()` (limietstatus), helpers zoals `e()` (XSS), `Request`, `View`, `ErrorHandler`. |
| Integratietests | `tests/Feature` | 147 | Een volledig verzoek door de hele app: **formulier/URL → router → CSRF-controle → middleware (inloggen, rol) → controller → validatie → repository → MariaDB → HTML-pagina**. Daarna controleert de test zowel de pagina (statuscode, redirect, tekst) als de **inhoud van de database**. |

De integratietests sturen het verzoek op dezelfde manier door de app als `public/index.php` dat doet (zie `tests/FeatureTestCase.php`). Zo wordt de echte code getest en geen nagemaakte versie. Alleen de browser zelf ontbreekt (zie 7.2).

### 2.2 Eigen testdatabase

- De tests gebruiken de database **`spendsmart_test`**, ingesteld in `phpunit.xml`. De echte database wordt nooit aangeraakt. Als de naam van de database niet op `_test` eindigt, weigert de test te starten (`tests/Support/TestDatabase.php`).
- Bij de start wordt `database/schema.sql` geladen. **Voor elke test** worden alle tabellen geleegd en wordt `database/seed.sql` opnieuw geladen. Elke test begint dus met dezelfde demodata (Sam, Sanne en de contentbeheerder) en tests kunnen elkaar niet beïnvloeden.

### 2.3 Naamgeving

De testnamen zijn in het Nederlands en beschrijven wat er gebeurt. Zo is het overzicht met `--testdox` direct leesbaar:

- `test_...`: happy flow (normaal gebruik)
- `test_unhappy_...`: verkeerd gebruik of foute invoer
- `test_randgeval_...`: grensgeval (precies op een grens, leeg, maximaal, maandwissel)

Elke testklasse heeft in de documentatie het nummer van de functionele eis (`@group FE-04`). Zo kun je één eis los testen:

```
/Applications/XAMPP/xamppfiles/bin/php vendor/bin/phpunit --group FE-04
```

### 2.4 Tests uitvoeren

```
/Applications/XAMPP/xamppfiles/bin/php tools/composer.phar test            # alle tests
/Applications/XAMPP/xamppfiles/bin/php tools/composer.phar test:coverage   # met coverage-rapport
```

Het HTML-coveragerapport komt in `coverage/html/index.html`. Installatie-instructies staan in de README (sectie *Tests*).

## 3. Resultaat

```
PHPUnit 9.6.37 by Sebastian Bergmann and contributors.

Runtime:       PHP 8.2.4 with PCOV 1.0.12
Configuration: phpunit.xml

...............................................................  63 / 238 ( 26%)
............................................................... 126 / 238 ( 52%)
............................................................... 189 / 238 ( 79%)
.................................................               238 / 238 (100%)

Time: 00:05.302, Memory: 12.00 MB

OK (238 tests, 565 assertions)
```

Unittests: 91 tests, 148 controles. Integratietests: 147 tests, 417 controles. Er zijn geen mislukte, overgeslagen of "risky" tests. Ook bleef het foutlog (`storage/logs/php-error.log`) na de testrun leeg, dus er zijn geen onverwachte 500-fouten opgetreden.

## 4. Dekking van de functionele eisen

Alle 12 geïmplementeerde functionele eisen zijn getest: **12/12 = 100 %** (eis: 90 %).

| ID | Functionele eis | Testklasse | Tests | Unhappy | Randgeval | Resultaat |
|---|---|---|---|---|---|---|
| FE-01 | Account maken | `RegistrationTest` | 12 | 4 | 4 | Geslaagd |
| FE-02 | Inloggen/uitloggen | `LoginTest` | 14 | 4 | 4 | Geslaagd |
| FE-03 | Eigen gegevens veilig beheren | `ProfileTest` | 10 | 3 | 2 | Geslaagd |
| FE-04 | Inkomst/uitgave met datum en categorie | `TransactionTest` | 14 | 5 | 4 | Geslaagd |
| FE-05 | Filteren per maand en categorie | `FilterTest` | 10 | – | 4 | Geslaagd |
| FE-06 | Inkomsten en uitgaven per maand optellen | `MonthTotalsTest` | 9 | – | 5 | Geslaagd |
| FE-07 | Eigen categorieën beheren | `CategoryTest` | 13 | 5 | 4 | Geslaagd |
| FE-08 | Spaardoelen beheren | `SavingsGoalTest` | 14 | 4 | 5 | Geslaagd |
| FE-09 | Waarschuwen bij overschrijding, zonder advies | `BudgetWarningTest`, `BudgetServiceTest` | 19 | – | 9 | Geslaagd |
| FE-10 | Categorievoorstellen beheren en overnemen | `CategorySuggestionTest` | 10 | 4 | 1 | Geslaagd |
| FE-11 | Leerteksten publiceren | `TipTest` | 11 | 3 | 2 | Geslaagd |
| FE-12 | Anonieme gebruiksaantallen | `StatisticsTest` | 6 | 1 | 1 | Geslaagd |
| TE-03/07/08 | Rollen, afscherming, foutpagina's | `AccessTest` | 15 | 10 | 2 | Geslaagd |
| TE-05/06 | Invoercontrole, bedragen in centen | `ValidatorTest`, `MoneyTest`, `MonthTest`, `SupportTest`, `RequestViewTest` | 81 | – | 21 | Geslaagd |

In totaal zijn er **43 unhappy-flowtests** en **68 randgevaltests** (eis: minimaal 5 van elk).

### 4.1 Voorbeelden van unhappy flows

| Test | Wat gebeurt er | Verwacht en gecontroleerd |
|---|---|---|
| `RegistrationTest::test_unhappy_emailadres_bestaat_al` | Registreren met `student@spendsmart.test` | 422, melding "Er bestaat al een account met dit e-mailadres.", geen extra gebruiker in de database |
| `LoginTest::test_unhappy_fout_wachtwoord` | Inloggen met fout wachtwoord | 422, algemene melding (verraadt niet of het account bestaat), niet ingelogd, poging opgeslagen |
| `LoginTest::test_unhappy_formulier_zonder_csrf_token_wordt_geweigerd` | Formulier zonder CSRF-token | 419 "Je sessie is verlopen", niet ingelogd |
| `TransactionTest::test_unhappy_categorie_van_een_andere_gebruiker` | Sam boekt in de categorie "Huur" van Sanne | 422 "Kies een van je eigen categorieën.", niets opgeslagen |
| `TransactionTest::test_unhappy_transactie_van_een_ander_bekijken_of_verwijderen` | Sam opent, wijzigt en verwijdert een transactie van Sanne | 3× 404, transactie bestaat nog |
| `CategoryTest::test_unhappy_categorie_met_transacties_kan_niet_worden_verwijderd` | Categorie met transacties verwijderen | Foutmelding, categorie bestaat nog |
| `ProfileTest::test_unhappy_huidig_wachtwoord_onjuist` | Wachtwoord wijzigen met fout huidig wachtwoord | 422, wachtwoordhash in database ongewijzigd |
| `CategorySuggestionTest::test_unhappy_inactief_voorstel_kan_niet_worden_overgenomen` | Gebruiker neemt inactief voorstel over | 404, geen categorie aangemaakt |
| `AccessTest::test_unhappy_gebruiker_kan_niet_bij_beheerpaginas` | Gebruiker opent de 3 beheerpagina's | 3× 403 |
| `AccessTest::test_unhappy_gebruiker_kan_geen_leertekst_aanmaken_via_formulier` | Gebruiker post direct naar `/content/tips` | 403, niets opgeslagen |

### 4.2 Voorbeelden van randgevallen

| Test | Grens | Verwacht en gecontroleerd |
|---|---|---|
| `LoginTest::test_randgeval_na_5_mislukte_pogingen_wordt_inloggen_geblokkeerd` | 5e mislukte poging | 6e poging geblokkeerd (429), ook met het juiste wachtwoord |
| `LoginTest::test_randgeval_4_mislukte_pogingen_blokkeren_nog_niet` | 4 pogingen | Inloggen lukt nog, pogingen worden gewist |
| `LoginTest::test_randgeval_oude_mislukte_pogingen_tellen_niet_mee` | Pogingen van 16 minuten geleden | Tellen niet mee (blokkade is 15 minuten) |
| `TransactionTest::test_randgeval_hoogst_toegestane_bedrag` | € 9.999.999,99 en € 10.000.000 | Eerste opgeslagen, tweede geweigerd |
| `MonthTotalsTest::test_randgeval_eerste_en_laatste_dag_van_de_maand_tellen_mee` | 31-12, 01-01, 31-01, 01-02 | Alleen 01-01 en 31-01 tellen mee voor januari |
| `MonthTotalsTest::test_randgeval_grote_bedragen_worden_exact_opgeteld` | 2 × maximum + 1 cent | Exact 1.999.999.999 cent, geen afrondingsfout |
| `BudgetServiceTest::test_randgeval_precies_80_procent_is_bijna_bereikt` / `..._79_procent_...` | 79 % en 80 % van de limiet | "Binnen limiet" en "Bijna bereikt" |
| `BudgetServiceTest::test_randgeval_een_cent_boven_de_limiet_is_overschreden` | Limiet + 1 cent | "Limiet overschreden" |
| `BudgetWarningTest::test_randgeval_limiet_nul_met_uitgave_toont_100_procent` | Limiet € 0,00 | Elke uitgave geeft een waarschuwing, balk op 100 % |
| `ValidatorTest::test_ongeldige_datum` (data sets) | 30 februari, 29-02 in 2027 | Geweigerd; 29-02-2028 (schrikkeljaar) wel geldig |
| `MonthTest::test_randgeval_vorige_maand_van_januari_...` | Jaarwisseling | Januari 2026 → december 2025 |
| `SavingsGoalTest::test_randgeval_gespaard_bedrag_boven_het_maximum` | Gespaard bedrag zou boven € 9.999.999,99 komen | Geweigerd, bedrag ongewijzigd |
| `RegistrationTest::test_randgeval_mislukte_registratie_laat_geen_halve_gegevens_achter` | Databasefout halverwege | Rollback: geen gebruiker en geen categorieën achtergebleven |

## 5. Werking en kwaliteit per functionele eis

**FE-01 Account maken.** Registreren werkt. Het wachtwoord wordt alleen gehasht opgeslagen (gecontroleerd met `password_verify`) en de gebruiker is direct ingelogd. Een nieuw account krijgt precies de *actieve* categorievoorstellen als startcategorieën. Een e-mailadres wordt opgeslagen in kleine letters en zonder spaties, zodat `STUDENT@...` niet als tweede account kan worden aangemaakt. Mislukt het opslaan halverwege, dan wordt alles teruggedraaid. *Kwaliteit: goed.*

**FE-02 Inloggen/uitloggen.** Inloggen stuurt elke rol naar de eigen startpagina. Bij een fout wachtwoord en bij een onbekend e-mailadres verschijnt dezelfde melding. Na 5 mislukte pogingen volgt 15 minuten blokkade, en oudere pogingen tellen niet mee. Formulieren zonder CSRF-token worden geweigerd. Een sessie van een verwijderd account wordt opgeruimd. Oude wachtwoordhashes worden bij het inloggen automatisch vernieuwd. *Kwaliteit: goed.*

**FE-03 Eigen gegevens beheren.** Naam en e-mail wijzigen, wachtwoord wijzigen (alleen met het huidige wachtwoord) en het account verwijderen werken allemaal. Bij verwijderen gaan ook alle transacties, categorieën en spaardoelen van die gebruiker weg, terwijl de gegevens van anderen blijven bestaan. Een contentbeheerder kan het eigen account niet verwijderen. *Kwaliteit: goed.*

**FE-04 Transacties.** Toevoegen, wijzigen en verwijderen werken. Bedragen worden exact in centen opgeslagen. Ongeldige bedragen (`12,345`, `0`, boven het maximum), ongeldige datums en categorieën van een andere gebruiker worden geweigerd met een duidelijke melding bij het veld. Transacties van een ander zijn niet op te vragen (404). HTML in een omschrijving wordt als tekst getoond (geen XSS). *Kwaliteit: goed.*

**FE-05 Filteren.** Filteren op maand, categorie en soort werkt, ook in combinatie. Een ongeldige maand of een categorie van een ander wordt genegeerd met een uitleg, en een lege maand toont een duidelijke lege-lijstmelding. *Kwaliteit: goed.*

**FE-06 Maandtotalen.** De totalen op het dashboard en boven de lijst kloppen tot op de cent met de demodata (inkomsten € 725,00, uitgaven € 315,58, saldo € 409,42). De eerste en laatste dag van de maand tellen mee, de dag erna niet. Een negatief saldo wordt goed getoond. *Kwaliteit: goed.*

**FE-07 Categorieën.** Aanmaken, wijzigen en verwijderen werken, net als de maandlimiet (ook € 0,00). Dubbele namen binnen dezelfde soort worden geweigerd. Een categorie met transacties kan niet worden verwijderd en kan niet van soort wisselen, zodat bestaande gegevens betrouwbaar blijven. *Kwaliteit: goed.*

**FE-08 Spaardoelen.** Aanmaken, wijzigen, bedrag toevoegen en verwijderen werken. Bij het bereiken van het doel verschijnt een felicitatie. Een streefdatum in het verleden wordt bij het aanmaken geweigerd, maar mag bij het wijzigen blijven staan. Het maximum wordt bewaakt. *Kwaliteit: goed.*

**FE-09 Budgetwaarschuwing.** Na een uitgave (of wijziging) boven de limiet verschijnt een waarschuwing met de tekst "geen financieel advies". Bij inkomsten, bij een categorie zonder limiet of binnen de limiet verschijnt geen waarschuwing. De grenzen 79 %, 80 %, 100 % en 100 % + 1 cent zijn precies getest. Het dashboard toont overschreden limieten met disclaimer. *Kwaliteit: goed.*

**FE-10 Categorievoorstellen.** De contentbeheerder kan voorstellen beheren en (de)activeren. Gebruikers zien alleen actieve voorstellen en kunnen ze overnemen, zonder dubbele categorie. Bij het verwijderen van een voorstel blijven de overgenomen categorieën bestaan. *Kwaliteit: goed, na het oplossen van een fout (zie 6).*

**FE-11 Leerteksten.** Publiceren, concept opslaan, publiceren en weer intrekken, en verwijderen werken. Gebruikers zien alleen gepubliceerde teksten. HTML in een titel wordt niet uitgevoerd. *Kwaliteit: goed.*

**FE-12 Statistieken.** De aantallen kloppen met de database: alleen gewone gebruikers tellen mee, en maanden zonder activiteit tellen als 0. De pagina bevat **geen** namen, e-mailadressen, omschrijvingen of bedragen (getest met `assertDontSee`). *Kwaliteit: goed.*

## 6. Gevonden fouten

| # | Gevonden door | Fout | Oplossing |
|---|---|---|---|
| 1 | `CategorySuggestionTest::test_gebruiker_ziet_en_neemt_voorstel_over` | Een voorstel overnemen (`/categories/adopt/{id}`) gaf een **500-fout**. De router gaf de waarde uit de URL door met de naam `$id`, maar de methode `adopt()` heet `$suggestionId`. PHP 8 geeft dan "Unknown named parameter". | De router geeft de waarden nu op volgorde door (`array_values`) in `app/Core/Router.php`. Daarna slaagden alle tests. |

Deze fout was bij de handmatige rooktest niet opgevallen. Dat laat zien wat geautomatiseerde integratietests toevoegen: ze testen elke route echt en elke keer opnieuw.

Om de app testbaar te maken zijn twee kleine aanpassingen gedaan:
- `Response::redirect()` gooit nu een `RedirectException` in plaats van `exit`. `public/index.php` vangt die op en stuurt de redirect. Voor de gebruiker verandert er niets, maar een test kan nu controleren *waarheen* wordt doorgestuurd.
- `Env::load()` overschrijft een bestaande omgevingsvariabele niet meer. Daardoor kan `phpunit.xml` de testdatabase instellen. Op Plesk kan dit ook handig zijn.

## 7. Reflectie op de kwaliteit van de tests

### 7.1 Sterke punten

- **Echte samenwerking getest.** De integratietests gebruiken de echte router, middleware, controllers, repositories, MariaDB en templates. Er wordt niets nagemaakt (geen mocks). Een fout in de SQL, een route of een template wordt dus gevonden. Fout 1 is daar een bewijs van.
- **Niet alleen de pagina, ook de database.** Na elke actie controleert de test of de database echt is veranderd, of juist níet (bij unhappy flows). Een test die alleen "status 200" controleert, zou een fout als "melding getoond maar toch opgeslagen" missen.
- **Beveiliging als testgeval.** Afscherming van gegevens van andere gebruikers (404), rollen (403), CSRF (419), XSS en de inlogblokkade worden automatisch gecontroleerd. Dat zijn de risico's die in de opdracht het zwaarst wegen.
- **Grenzen precies getest.** Bij de limietstatus, bedragen, datums en maandgrenzen wordt de waarde *op* de grens en *net erover* getest. Daar zitten in de praktijk de meeste fouten.
- **Onafhankelijk en snel.** Elke test begint met dezelfde data. De volledige set draait in ongeveer 5 seconden, dus het is makkelijk om na elke wijziging alles opnieuw te testen.
- **Leesbaar.** De Nederlandse testnamen beschrijven het verwachte gedrag. Het testoverzicht leest daardoor als een lijst met eisen.

### 7.2 Beperkingen en wat niet automatisch is getest

- **Geen browsertests.** De tests lezen de HTML, maar draaien geen echte browser. Het responsive ontwerp (TE-02), het inklapbare menu, de JavaScript-bevestiging bij verwijderen en de kleuren zijn daarom handmatig getest op een telefoon en een laptop (zie 7.3). Op een tablet is niet apart getest.
- **Headers en cookies.** `Session::start()` (cookie-instellingen), `Response::sendSecurityHeaders()` (CSP en dergelijke) en `RedirectException::send()` draaien alleen in een echte webserver. Op de command line kunnen headers niet worden uitgelezen. Daardoor scoort `app/Core` "maar" 86,6 %. Deze onderdelen zijn gecontroleerd op de live server (zie 7.3).
- **`Database::connection()` lijkt ongetest (15 %),** maar wordt in elke databasetest gebruikt. De verbinding wordt al gemaakt voordat de coverage-meting start, dus de regels worden niet meegeteld.
- **Niet-bereikbare code.** In `ProfileController::destroy()` staat een extra rolcontrole die nooit wordt bereikt, omdat de route al `role:user` vereist (getest: 403). De rollback in `UserRepository::delete()` is niet getest, omdat een databasefout daar moeilijk na te bootsen is.
- **Coverage is geen garantie.** 96 % van de regels is uitgevoerd, maar dat betekent niet dat elke combinatie is getest. Daarom zijn naast de coverage bewust unhappy flows en randgevallen gekozen op basis van de eisen, en niet op basis van de code.
- **Afhankelijk van de demodata en de datum.** De verwachte bedragen komen uit `seed.sql`. Als de demodata verandert, moeten die tests worden aangepast. De demodata gebruikt datums die afhangen van vandaag. Daarom rekenen tests met `Month::current()` in plaats van vaste maanden.
- **Geen belasting- of prestatietests.** Hoe de app zich gedraagt met veel gebruikers tegelijk is niet getest. Voor een MVP met oefengegevens vind ik dat acceptabel.

### 7.3 Handmatige test op de live server

Getest op 3 oktober 2026 op **https://spendsmart.s2214593.jouw.website** (Plesk, HTTPS met Let's Encrypt).

| # | Apparaat | Controle | Resultaat |
|---|---|---|---|
| 1 | Telefoon | Menu klapt open en dicht met de menuknop | Geslaagd |
| 2 | Telefoon | Dashboard is leesbaar zonder horizontaal scrollen | Geslaagd |
| 3 | Telefoon | Transacties worden als kaartjes onder elkaar getoond | Geslaagd |
| 4 | Telefoon | Uitgave van € 30 bij Vervoer geeft een waarschuwing met "geen financieel advies" | Geslaagd |
| 5 | Laptop | Verwijderen vraagt eerst om bevestiging (JavaScript) | Geslaagd |
| 6 | Laptop | Contentbeheerder ziet statistieken zonder namen of bedragen | Geslaagd |
| 7 | Laptop (Terminal) | `.env`, `database/seed.sql` en `composer.json` zijn niet op te vragen (403/404) | Geslaagd |
| 8 | Laptop (Terminal) | Headers `Content-Security-Policy`, `X-Frame-Options: DENY` en `X-Content-Type-Options: nosniff` worden meegestuurd | Geslaagd |
| 9 | Laptop (Terminal) | Sessiecookie heeft `Secure`, `HttpOnly` en `SameSite=Lax` | Geslaagd |
| 10 | Laptop (Terminal) | Inloggen met demo-account stuurt door naar het dashboard; bedragen kloppen | Geslaagd |

Controles 7 t/m 10 zijn gedaan met `curl` vanaf de command line.

## 8. Conclusie en aanbeveling

**Conclusie.** Alle 12 functionele eisen zijn getest en werken zoals bedoeld. 238 tests met 565 controles slagen, waarvan 43 unhappy flows en 68 randgevallen. 96,4 % van de code wordt door de tests uitgevoerd. De samenwerking tussen frontend, backend en database is per eis met integratietests aangetoond. De belangrijkste risico's voor MoneyMinds zijn ook getest: privacy (niemand ziet gegevens van een ander), juiste bedragen (centen, exacte totalen) en geen adviesclaim (disclaimer bij elke waarschuwing). De enige gevonden fout (voorstel overnemen) is opgelost en wordt nu door een test bewaakt.

De handmatige test op de live server (7.3) bevestigt dat de app ook op telefoon en laptop en via HTTPS goed werkt.

**Aanbeveling.** SpendSmart is als MVP **bruikbaar en betrouwbaar genoeg** om in gebruik te nemen met oefengegevens. Voor een volgende versie raad ik aan:

1. Browsertests toe te voegen (bijvoorbeeld met Playwright), zodat de handmatige controles uit 7.3 ook automatisch gaan en ook op een tablet worden gedaan.
2. De tests automatisch te laten draaien bij elke push naar GitHub (GitHub Actions met een MariaDB-service), zodat een fout nooit ongemerkt live gaat.
3. Bij elke nieuwe functie eerst een unhappy-flow- en randgevaltest te schrijven, zodat het huidige niveau behouden blijft.

## Bijlage: coverage-uitvoer (PHPUnit + PCOV)

Gegenereerd met `composer test:coverage`. Het volledige, klikbare rapport staat in `coverage/html/index.html`.

```
Code Coverage Report:
  2026-10-07 21:22:10

 Summary:
  Classes: 79.07% (34/43)
  Methods: 95.71% (223/233)
  Lines:   96.41% (2151/2231)

App\Controllers\AuthController                 Methods: 100.00% ( 5/ 5)   Lines: 100.00% ( 75/ 75)
App\Controllers\CategoryController             Methods: 100.00% (10/10)   Lines: 100.00% ( 89/ 89)
App\Controllers\Content\StatisticsController   Methods: 100.00% ( 1/ 1)   Lines: 100.00% (  8/  8)
App\Controllers\Content\SuggestionController   Methods: 100.00% ( 9/ 9)   Lines: 100.00% ( 61/ 61)
App\Controllers\Content\TipController          Methods: 100.00% ( 9/ 9)   Lines: 100.00% ( 52/ 52)
App\Controllers\DashboardController            Methods: 100.00% ( 1/ 1)   Lines: 100.00% ( 18/ 18)
App\Controllers\HomeController                 Methods: 100.00% ( 1/ 1)   Lines: 100.00% (  6/  6)
App\Controllers\ProfileController              Methods:  80.00% ( 4/ 5)   Lines:  96.67% ( 58/ 60)
App\Controllers\SavingsGoalController          Methods: 100.00% (10/10)   Lines: 100.00% ( 71/ 71)
App\Controllers\TipController                  Methods: 100.00% ( 1/ 1)   Lines: 100.00% (  4/  4)
App\Controllers\TransactionController          Methods: 100.00% (13/13)   Lines: 100.00% (106/106)
App\Core\Auth                                  Methods: 100.00% ( 7/ 7)   Lines: 100.00% ( 18/ 18)
App\Core\Controller                            Methods: 100.00% ( 8/ 8)   Lines: 100.00% ( 16/ 16)
App\Core\Csrf                                  Methods: 100.00% ( 2/ 2)   Lines: 100.00% (  6/  6)
App\Core\Database                              Methods:   0.00% ( 0/ 1)   Lines:  15.38% (  2/ 13)
App\Core\Env                                   Methods:  50.00% ( 1/ 2)   Lines:  92.31% ( 12/ 13)
App\Core\ErrorHandler                          Methods:   0.00% ( 0/ 2)   Lines:  75.00% ( 15/ 20)
App\Core\Flash                                 Methods: 100.00% ( 2/ 2)   Lines: 100.00% (  7/  7)
App\Core\HttpException                         Methods: 100.00% ( 2/ 2)   Lines: 100.00% (  2/  2)
App\Core\Middleware                            Methods: 100.00% ( 4/ 4)   Lines: 100.00% ( 14/ 14)
App\Core\RedirectException                     Methods:  66.67% ( 2/ 3)   Lines:  66.67% (  2/  3)
App\Core\Request                               Methods:  88.89% ( 8/ 9)   Lines:  94.44% ( 17/ 18)
App\Core\Response                              Methods:  50.00% ( 1/ 2)   Lines:  20.00% (  1/  5)
App\Core\Router                                Methods: 100.00% ( 4/ 4)   Lines: 100.00% ( 33/ 33)
App\Core\Session                               Methods:  80.00% ( 4/ 5)   Lines:  26.32% (  5/ 19)
App\Core\Validator                             Methods: 100.00% (12/12)   Lines: 100.00% ( 76/ 76)
App\Core\View                                  Methods: 100.00% ( 5/ 5)   Lines: 100.00% ( 12/ 12)
App\Repositories\CategoryRepository            Methods: 100.00% ( 8/ 8)   Lines: 100.00% ( 50/ 50)
App\Repositories\CategorySuggestionRepository  Methods: 100.00% ( 9/ 9)   Lines: 100.00% ( 37/ 37)
App\Repositories\LoginAttemptRepository        Methods: 100.00% ( 3/ 3)   Lines: 100.00% ( 13/ 13)
App\Repositories\Repository                    Methods: 100.00% ( 6/ 6)   Lines: 100.00% ( 16/ 16)
App\Repositories\SavingsGoalRepository         Methods: 100.00% ( 7/ 7)   Lines: 100.00% ( 41/ 41)
App\Repositories\StatisticsRepository          Methods: 100.00% ( 5/ 5)   Lines: 100.00% ( 22/ 22)
App\Repositories\TipRepository                 Methods: 100.00% ( 7/ 7)   Lines: 100.00% ( 38/ 38)
App\Repositories\TransactionRepository         Methods: 100.00% (10/10)   Lines: 100.00% ( 56/ 56)
App\Repositories\UserRepository                Methods:  87.50% ( 7/ 8)   Lines:  90.91% ( 30/ 33)
App\Services\BudgetService                     Methods: 100.00% ( 8/ 8)   Lines: 100.00% ( 54/ 54)
App\Services\RegistrationService               Methods: 100.00% ( 2/ 2)   Lines: 100.00% ( 15/ 15)
App\Services\StatisticsService                 Methods: 100.00% ( 3/ 3)   Lines: 100.00% ( 29/ 29)
App\Support\CategoryType                       Methods: 100.00% ( 3/ 3)   Lines: 100.00% (  3/  3)
App\Support\Money                              Methods: 100.00% ( 4/ 4)   Lines: 100.00% ( 14/ 14)
App\Support\Month                              Methods: 100.00% (13/13)   Lines: 100.00% ( 19/ 19)
App\Support\Role                               Methods: 100.00% ( 2/ 2)   Lines: 100.00% ( 10/ 10)
```

Dekking per map (uit het HTML-rapport):

| Map | Regels gedekt |
|---|---|
| `app/Controllers` | 99,64 % |
| `app/Core` | 86,55 % |
| `app/Repositories` | 99,02 % |
| `app/Services` | 100 % |
| `app/Support` | 100 % |
| `app/Views` | 97,23 % |
| `app/helpers.php` | 97,14 % |
