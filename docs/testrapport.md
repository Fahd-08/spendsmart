# Testrapport SpendSmart (KT1-W4)

| | |
|---|---|
| Student | Fahd El Fechka (2214593, klas 24A) |
| Project | SpendSmart, budgettool voor MoneyMinds |
| Testdatum | 1 oktober 2026, bijgewerkt op 7 oktober 2026 (versie v1.7) |
| Testomgeving | macOS met XAMPP: PHP 8.2.4 en MariaDB 10.4.28 |
| Testframework | PHPUnit 9.6, coverage gemeten met PCOV 1.0.12 |
| Resultaat | 238 tests met 565 controles, alles geslaagd |
| Codedekking | 96,41 % van de regels en 95,71 % van de methodes |
| Functionele eisen getest | 12 van de 12 (100 %) |

## 1. Doel

In dit rapport laat ik zien dat alle functionele eisen uit KT1-W3 (FE-01 t/m FE-12) werken. Ik heb niet alleen getest of het werkt als je alles goed invult, maar ook wat er gebeurt als je iets fout doet (unhappy flows) en wat er gebeurt precies op een grens (randgevallen). Ook heb ik getest of de frontend, de backend en de database goed samenwerken.

## 2. Hoe ik heb getest

### 2.1 Twee soorten tests

Ik heb twee soorten tests geschreven:

| Soort | Map | Aantal | Wat wordt getest |
|---|---|---|---|
| Unittests | `tests/Unit` | 91 | Losse klassen zonder database, bijvoorbeeld `Money` (bedragen in centen), `Month` (het maandfilter), `Validator` (alle invoerregels) en `BudgetService` (de status van een limiet). |
| Integratietests | `tests/Feature` | 147 | Een heel verzoek door de app: van formulier of URL via de router, de CSRF-controle, de middleware, de controller en de repository naar de database, en weer terug als HTML-pagina. Daarna controleer ik de pagina en ook wat er in de database staat. |

De integratietests sturen een verzoek op dezelfde manier door de app als `public/index.php`. Zo test ik de echte code en geen nagemaakte versie. Het enige wat ontbreekt is een echte browser. Daar kom ik in hoofdstuk 7 op terug.

### 2.2 Een aparte testdatabase

De tests gebruiken een eigen database, `spendsmart_test`. Dat staat ingesteld in `phpunit.xml`. Zo kan ik mijn echte database nooit per ongeluk leegmaken. Als de naam van de database niet op `_test` eindigt, start de test niet eens.

Voor elke test worden alle tabellen leeggemaakt en wordt de demodata uit `database/seed.sql` opnieuw geladen. Elke test begint dus met dezelfde gegevens (de gebruikers Sam en Sanne en de contentbeheerder), en tests kunnen elkaar niet beïnvloeden.

### 2.3 Namen van de tests

Ik heb de tests Nederlandse namen gegeven die zeggen wat er gebeurt:

- `test_...` is normaal gebruik (happy flow)
- `test_unhappy_...` is foute invoer of iets wat niet mag
- `test_randgeval_...` is een test precies op een grens, bijvoorbeeld het hoogste bedrag of de laatste dag van de maand

Elke testklasse heeft het nummer van de eis die hij test (`@group FE-04`). Zo kan ik ook één eis los testen:

```
/Applications/XAMPP/xamppfiles/bin/php vendor/bin/phpunit --group FE-04
```

### 2.4 Tests uitvoeren

```
/Applications/XAMPP/xamppfiles/bin/php tools/composer.phar test
/Applications/XAMPP/xamppfiles/bin/php tools/composer.phar test:coverage
```

Het eerste commando draait alle tests. Het tweede maakt ook het coverage-rapport, dat daarna in `coverage/html/index.html` staat. Hoe je alles installeert, staat in de README bij het kopje Tests.

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

De 91 unittests doen samen 148 controles en de 147 integratietests doen er 417. Er zijn geen tests mislukt of overgeslagen. Na het draaien van de tests was het foutlog (`storage/logs/php-error.log`) nog leeg, dus er waren ook geen onverwachte fouten.

## 4. Welke eisen zijn getest

Alle 12 functionele eisen zijn getest. Dat is 100 %, en de eis was minimaal 90 %.

| ID | Functionele eis | Testklasse | Tests | Unhappy | Randgeval | Resultaat |
|---|---|---|---|---|---|---|
| FE-01 | Account maken | `RegistrationTest` | 12 | 4 | 4 | Geslaagd |
| FE-02 | Inloggen en uitloggen | `LoginTest` | 14 | 4 | 4 | Geslaagd |
| FE-03 | Eigen gegevens veilig beheren | `ProfileTest` | 10 | 3 | 2 | Geslaagd |
| FE-04 | Inkomst of uitgave registreren | `TransactionTest` | 14 | 5 | 4 | Geslaagd |
| FE-05 | Filteren per maand en categorie | `FilterTest` | 10 | 0 | 4 | Geslaagd |
| FE-06 | Inkomsten en uitgaven per maand optellen | `MonthTotalsTest` | 9 | 0 | 5 | Geslaagd |
| FE-07 | Eigen categorieën beheren | `CategoryTest` | 13 | 5 | 4 | Geslaagd |
| FE-08 | Spaardoelen beheren | `SavingsGoalTest` | 14 | 4 | 5 | Geslaagd |
| FE-09 | Waarschuwen bij overschrijding, zonder advies | `BudgetWarningTest`, `BudgetServiceTest` | 19 | 0 | 9 | Geslaagd |
| FE-10 | Categorievoorstellen beheren en overnemen | `CategorySuggestionTest` | 10 | 4 | 1 | Geslaagd |
| FE-11 | Leerteksten publiceren | `TipTest` | 11 | 3 | 2 | Geslaagd |
| FE-12 | Anonieme gebruiksaantallen | `StatisticsTest` | 6 | 1 | 1 | Geslaagd |
| TE-03, TE-07, TE-08 | Rollen, afscherming en foutpagina's | `AccessTest` | 15 | 10 | 2 | Geslaagd |
| TE-05, TE-06 | Invoercontrole en bedragen in centen | `ValidatorTest`, `MoneyTest`, `MonthTest`, `SupportTest`, `RequestViewTest` | 81 | 0 | 21 | Geslaagd |

In totaal heb ik 43 unhappy-flowtests en 68 randgevaltests. De eis was minimaal 5 van elk.

### 4.1 Voorbeelden van unhappy flows

| Test | Wat er gebeurt | Wat ik controleer |
|---|---|---|
| `RegistrationTest::test_unhappy_emailadres_bestaat_al` | Registreren met een e-mailadres dat al bestaat | Status 422, de melding "Er bestaat al een account met dit e-mailadres." en er is geen gebruiker bijgekomen |
| `LoginTest::test_unhappy_fout_wachtwoord` | Inloggen met een fout wachtwoord | Status 422 en een algemene melding, zodat je niet kunt zien of het account bestaat. Niet ingelogd en de poging is opgeslagen |
| `LoginTest::test_unhappy_formulier_zonder_csrf_token_wordt_geweigerd` | Een formulier versturen zonder CSRF-token | Status 419 en niet ingelogd |
| `TransactionTest::test_unhappy_categorie_van_een_andere_gebruiker` | Sam probeert iets te boeken in de categorie "Huur" van Sanne | Status 422, de melding "Kies een van je eigen categorieën." en er is niets opgeslagen |
| `TransactionTest::test_unhappy_transactie_van_een_ander_bekijken_of_verwijderen` | Sam probeert een transactie van Sanne te openen, te wijzigen en te verwijderen | Drie keer status 404 en de transactie bestaat nog |
| `CategoryTest::test_unhappy_categorie_met_transacties_kan_niet_worden_verwijderd` | Een categorie verwijderen waar nog transacties in staan | Foutmelding en de categorie bestaat nog |
| `ProfileTest::test_unhappy_huidig_wachtwoord_onjuist` | Wachtwoord wijzigen met een fout huidig wachtwoord | Status 422 en het wachtwoord in de database is niet veranderd |
| `CategorySuggestionTest::test_unhappy_inactief_voorstel_kan_niet_worden_overgenomen` | Een gebruiker neemt een voorstel over dat niet actief is | Status 404 en er is geen categorie gemaakt |
| `AccessTest::test_unhappy_gebruiker_kan_niet_bij_beheerpaginas` | Een gewone gebruiker opent de drie beheerpagina's | Drie keer status 403 |
| `AccessTest::test_unhappy_gebruiker_kan_geen_leertekst_aanmaken_via_formulier` | Een gewone gebruiker stuurt zelf een formulier naar `/content/tips` | Status 403 en er is niets opgeslagen |

### 4.2 Voorbeelden van randgevallen

| Test | De grens | Wat ik controleer |
|---|---|---|
| `LoginTest::test_randgeval_na_5_mislukte_pogingen_wordt_inloggen_geblokkeerd` | 5 mislukte pogingen | De 6e poging wordt geblokkeerd (429), ook met het goede wachtwoord |
| `LoginTest::test_randgeval_4_mislukte_pogingen_blokkeren_nog_niet` | 4 mislukte pogingen | Inloggen lukt nog en de pogingen worden gewist |
| `LoginTest::test_randgeval_oude_mislukte_pogingen_tellen_niet_mee` | Pogingen van 16 minuten geleden | Die tellen niet mee, want de blokkade duurt 15 minuten |
| `TransactionTest::test_randgeval_hoogst_toegestane_bedrag` | € 9.999.999,99 en € 10.000.000 | Het eerste bedrag wordt opgeslagen, het tweede niet |
| `MonthTotalsTest::test_randgeval_eerste_en_laatste_dag_van_de_maand_tellen_mee` | 31 december, 1 januari, 31 januari en 1 februari | Alleen 1 en 31 januari tellen mee voor januari |
| `MonthTotalsTest::test_randgeval_grote_bedragen_worden_exact_opgeteld` | Twee keer het maximum plus 1 cent | Het totaal is precies 1.999.999.999 cent, zonder afrondingsfout |
| `BudgetServiceTest::test_randgeval_precies_80_procent_is_bijna_bereikt` en de test voor 79 procent | 79 % en 80 % van de limiet | "Binnen limiet" en "Bijna bereikt" |
| `BudgetServiceTest::test_randgeval_een_cent_boven_de_limiet_is_overschreden` | De limiet plus 1 cent | "Limiet overschreden" |
| `BudgetWarningTest::test_randgeval_limiet_nul_met_uitgave_toont_100_procent` | Een limiet van € 0,00 | Elke uitgave geeft een waarschuwing en de balk staat op 100 % |
| `ValidatorTest::test_ongeldige_datum` | 30 februari en 29 februari 2027 | Allebei geweigerd, terwijl 29 februari 2028 (schrikkeljaar) wel goed is |
| `MonthTest::test_randgeval_vorige_maand_van_januari_is_december_vorig_jaar` | De jaarwisseling | De maand vóór januari 2026 is december 2025 |
| `SavingsGoalTest::test_randgeval_gespaard_bedrag_boven_het_maximum` | Het gespaarde bedrag zou boven € 9.999.999,99 komen | Geweigerd en het bedrag is niet veranderd |
| `RegistrationTest::test_randgeval_mislukte_registratie_laat_geen_halve_gegevens_achter` | Een databasefout halverwege het registreren | Alles is teruggedraaid: geen half account en geen losse categorieën |

## 5. Hoe goed werkt elke eis

**FE-01 Account maken.** Registreren werkt. Het wachtwoord wordt alleen als hash opgeslagen en je bent na het registreren meteen ingelogd. Een nieuw account krijgt de actieve categorievoorstellen als startcategorieën. E-mailadressen sla ik op in kleine letters en zonder spaties, zodat iemand niet met `STUDENT@...` een tweede account kan maken. Als het opslaan halverwege misgaat, wordt alles teruggedraaid. Dit werkt goed.

**FE-02 Inloggen en uitloggen.** Na het inloggen kom je op de startpagina van je rol. Bij een fout wachtwoord en bij een onbekend e-mailadres krijg je dezelfde melding. Na 5 mislukte pogingen kun je 15 minuten niet inloggen, en oudere pogingen tellen niet mee. Formulieren zonder CSRF-token worden geweigerd. Werkt goed.

**FE-03 Eigen gegevens beheren.** Je naam en e-mail wijzigen, je wachtwoord wijzigen (alleen met je huidige wachtwoord) en je account verwijderen werken allemaal. Bij het verwijderen gaan ook al je transacties, categorieën en spaardoelen weg, maar de gegevens van andere gebruikers blijven staan. Een contentbeheerder kan zijn eigen account niet verwijderen. Werkt goed.

**FE-04 Transacties.** Toevoegen, wijzigen en verwijderen werken. Bedragen worden precies in centen opgeslagen. Een fout bedrag (zoals `12,345` of `0`), een datum die niet bestaat of een categorie van iemand anders wordt geweigerd, met een melding bij het veld. Transacties van een ander kun je niet openen (404). HTML in een omschrijving wordt als gewone tekst getoond, dus XSS werkt niet. Werkt goed.

**FE-05 Filteren.** Filteren op maand, categorie en soort werkt, ook samen. Als je een maand invult die niet bestaat of een categorie van een ander kiest, wordt dat genegeerd en krijg je een melding. Bij een lege maand zie je een duidelijke tekst. Werkt goed.

**FE-06 Maandtotalen.** De totalen op het dashboard kloppen tot op de cent met de demodata: € 725,00 inkomsten, € 315,58 uitgaven en € 409,42 saldo. De eerste en de laatste dag van de maand tellen mee, de dag daarna niet. Een negatief saldo wordt ook goed getoond. Werkt goed.

**FE-07 Categorieën.** Toevoegen, wijzigen en verwijderen werken, en je kunt een maandlimiet instellen (ook € 0,00). Twee categorieën met dezelfde naam en soort kan niet. Een categorie waar nog transacties in staan, kun je niet verwijderen en ook niet van soort veranderen. Zo blijven bestaande gegevens kloppen. Werkt goed.

**FE-08 Spaardoelen.** Toevoegen, wijzigen, geld toevoegen en verwijderen werken. Als je je doel bereikt, krijg je een felicitatie. Bij een nieuw doel mag de streefdatum niet in het verleden liggen. Bij het wijzigen mag een oude datum wel blijven staan. Werkt goed.

**FE-09 Budgetwaarschuwing.** Als een uitgave boven de limiet komt, krijg je een waarschuwing met de tekst "geen financieel advies". Bij een inkomst, bij een categorie zonder limiet of als je nog onder de limiet zit, komt er geen waarschuwing. Ik heb de grenzen 79 %, 80 %, 100 % en 100 % plus 1 cent apart getest. Werkt goed.

**FE-10 Categorievoorstellen.** De contentbeheerder kan voorstellen maken, wijzigen, aan- en uitzetten en verwijderen. Gebruikers zien alleen actieve voorstellen en kunnen ze overnemen, zonder dat er een dubbele categorie ontstaat. Als een voorstel wordt verwijderd, blijven de overgenomen categorieën gewoon bestaan. Dit werkt nu goed, maar eerst zat hier een fout in (zie hoofdstuk 6).

**FE-11 Leerteksten.** Een tekst als concept opslaan, publiceren, weer terugzetten naar concept en verwijderen werkt. Gebruikers zien alleen gepubliceerde teksten. HTML in een titel wordt niet uitgevoerd. Werkt goed.

**FE-12 Statistieken.** De aantallen kloppen met de database. Alleen gewone gebruikers tellen mee en een maand zonder activiteit telt als 0. Op de pagina staan geen namen, e-mailadressen, omschrijvingen of bedragen. Dat heb ik ook getest. Werkt goed.

## 6. Gevonden fout

Door de tests heb ik één echte fout gevonden. Als een gebruiker een categorievoorstel wilde overnemen (`/categories/adopt/{id}`), kreeg hij een foutpagina (500). De router gaf het getal uit de URL door met de naam `$id`, maar in de methode `adopt()` heet die parameter `$suggestionId`. PHP 8 geeft dan de fout "Unknown named parameter". De test `CategorySuggestionTest::test_gebruiker_ziet_en_neemt_voorstel_over` liet dit zien.

Ik heb het opgelost in `app/Core/Router.php`: de router geeft de waarden nu op volgorde door (met `array_values`) en niet meer op naam. Daarna slaagden alle tests.

Bij het handmatig testen was deze fout mij niet opgevallen. Dat laat goed zien waarom automatische tests nuttig zijn: ze testen elke route echt, en elke keer opnieuw.

Om de app goed te kunnen testen heb ik ook twee kleine dingen veranderd:

- `Response::redirect()` gebruikt geen `exit` meer, maar gooit een `RedirectException`. Die wordt in `public/index.php` opgevangen. Voor de gebruiker verandert er niets, maar een test kan nu zien waar de app naartoe doorstuurt.
- `Env::load()` overschrijft geen instelling meer die al bestaat. Daardoor kan `phpunit.xml` de testdatabase instellen.

## 7. Reflectie op mijn tests

### 7.1 Wat goed is

De integratietests gebruiken de echte router, controllers, database en templates. Ik heb niets nagemaakt (geen mocks). Daardoor vinden de tests ook fouten in de SQL, in een route of in een template. De fout uit hoofdstuk 6 is daar een voorbeeld van.

Ik controleer niet alleen de pagina, maar ook de database. Bij een unhappy flow kijk ik of er echt niets is opgeslagen. Een test die alleen naar de statuscode kijkt, zou een fout als "er staat een foutmelding maar het is toch opgeslagen" missen.

De beveiliging zit in de tests. Ik test automatisch dat je niet bij de gegevens van een ander kunt (404), dat je met de verkeerde rol niet bij een pagina kunt (403), dat formulieren zonder CSRF-token worden geweigerd (419), dat XSS niet werkt en dat inloggen wordt geblokkeerd na te veel pogingen. Voor MoneyMinds zijn dat de belangrijkste risico's.

Bij bedragen, datums, limieten en maanden test ik precies op de grens en net erover. Daar gaat het in de praktijk het vaakst mis.

Alle tests samen duren ongeveer 5 seconden. Ik kan dus na elke wijziging alles opnieuw testen.

### 7.2 Wat beter kan, en wat niet automatisch getest is

Ik heb geen tests met een echte browser. De tests lezen de HTML, maar klikken niet echt. Daarom heb ik het responsive ontwerp, het menu op mobiel, de bevestigingsvraag bij verwijderen en de kleuren met de hand getest op mijn telefoon en laptop (zie 7.3). Op een tablet heb ik niet apart getest.

Sommige code werkt alleen op een echte webserver, bijvoorbeeld het instellen van de sessiecookie en de beveiligingsheaders. Die kan ik op de command line niet testen. Daardoor is de dekking van `app/Core` lager (86,6 %). Deze dingen heb ik op de live server gecontroleerd (zie 7.3).

`Database::connection()` lijkt in het rapport bijna niet getest (15 %), maar wordt in elke databasetest gebruikt. De verbinding wordt al gemaakt voordat de meting begint, daarom tellen die regels niet mee.

In `ProfileController::destroy()` staat een extra controle op de rol die nooit wordt bereikt, omdat de route dat al controleert. Het terugdraaien in `UserRepository::delete()` heb ik niet getest, omdat het lastig is om daar een databasefout na te bootsen.

Een hoge coverage betekent niet dat alles getest is. Het zegt alleen dat de regels zijn uitgevoerd. Daarom heb ik de unhappy flows en randgevallen gekozen vanuit de eisen, en niet alleen gekeken naar welke regels nog niet geraakt werden.

De verwachte bedragen in de tests komen uit `seed.sql`. Als ik de demodata verander, moet ik die tests ook aanpassen.

Ik heb niet getest hoe de app reageert als heel veel mensen hem tegelijk gebruiken. Voor een eerste versie met oefengegevens vind ik dat niet nodig.

### 7.3 Handmatige test op de live server

Op 3 oktober 2026 heb ik de live site getest: https://spendsmart.s2214593.jouw.website (Plesk, met HTTPS).

| # | Apparaat | Wat ik heb gecontroleerd | Resultaat |
|---|---|---|---|
| 1 | Telefoon | Het menu gaat open en dicht met de menuknop | Geslaagd |
| 2 | Telefoon | Het dashboard is leesbaar zonder opzij te scrollen | Geslaagd |
| 3 | Telefoon | Transacties staan als kaartjes onder elkaar | Geslaagd |
| 4 | Telefoon | Een uitgave van € 30 bij Vervoer geeft een waarschuwing met "geen financieel advies" | Geslaagd |
| 5 | Laptop | Bij verwijderen wordt eerst gevraagd of je het zeker weet | Geslaagd |
| 6 | Laptop | De contentbeheerder ziet statistieken zonder namen of bedragen | Geslaagd |
| 7 | Laptop (Terminal) | `.env`, `database/seed.sql` en `composer.json` zijn niet op te vragen (403 of 404) | Geslaagd |
| 8 | Laptop (Terminal) | De beveiligingsheaders (`Content-Security-Policy`, `X-Frame-Options` en `X-Content-Type-Options`) worden meegestuurd | Geslaagd |
| 9 | Laptop (Terminal) | De sessiecookie heeft `Secure`, `HttpOnly` en `SameSite=Lax` | Geslaagd |
| 10 | Laptop (Terminal) | Inloggen met een demo-account werkt en de bedragen kloppen | Geslaagd |

Controle 7 tot en met 10 zijn gedaan met `curl` in de Terminal.

## 8. Conclusie en aanbeveling

Alle 12 functionele eisen zijn getest en werken. De 238 tests slagen allemaal, waaronder 43 unhappy flows en 68 randgevallen. De tests voeren 96,4 % van de code uit. Met de integratietests heb ik per eis laten zien dat de frontend, de backend en de database goed samenwerken. Ook de dingen die voor MoneyMinds het belangrijkst zijn, zijn getest: niemand kan de gegevens van een ander zien, de bedragen kloppen tot op de cent en bij elke waarschuwing staat dat het geen financieel advies is. De ene fout die ik vond, is opgelost en wordt nu door een test gecontroleerd. De handmatige test laat zien dat de app ook live, op telefoon en laptop, goed werkt.

Mijn conclusie is dat SpendSmart als eerste versie goed genoeg is om te gebruiken met oefengegevens.

Voor een volgende versie raad ik drie dingen aan:

1. Tests met een echte browser toevoegen (bijvoorbeeld met Playwright). Dan kunnen de handmatige controles uit 7.3 ook automatisch, en ook op een tablet.
2. De tests automatisch laten draaien bij elke push naar GitHub (met GitHub Actions). Dan komt een fout nooit ongemerkt online.
3. Bij elke nieuwe functie meteen ook een unhappy-flowtest en een randgevaltest schrijven.

## Bijlage: coverage-uitvoer van PHPUnit

Dit is de uitvoer van `composer test:coverage`. Het hele rapport, waarin je kunt doorklikken, staat in `coverage/html/index.html`.

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
