# Verantwoording realisatie SpendSmart (KT1-W3)

Student: Fahd El Fechka (2214593, klas 24A)

Definitieve versie: GitHub-tag v1.7

In dit document laat ik per functie zien bij welke eis, welk deel van mijn ontwerp en welke taak uit mijn planning hij hoort. Daarna beschrijf ik wat er anders is geworden dan in mijn ontwerp, en waarom.

Even over de nummers: in de kolommen ID en Eis gebruik ik de functionele eisen uit de opdracht van MoneyMinds. Die nummers gebruik ik ook in de code, de tests en het testrapport. In mijn technisch ontwerp (TO) en mijn planning had ik de eisen anders genummerd. Zo is FE-03 in mijn ontwerp "Inkomsten beheren", maar in deze tabel valt dat onder FE-04. Daarom staat er in de kolom Ontwerp het nummer uit mijn technisch ontwerp, en in de kolom Taak de taaknummers uit mijn planning, met tussen haakjes het eisnummer uit de planning (P).

## Functionele eisen

| ID | Eis (opdracht) | Wat ik heb gebouwd | Belangrijkste code | Ontwerp | Taak | Status |
|---|---|---|---|---|---|---|
| FE-01 | Account maken | Registratieformulier met naam, e-mail, wachtwoord (twee keer) en een vinkje dat je met oefengegevens werkt | `AuthController::register`, `RegistrationService`, `auth/register.php` | TO FE-01, hoofdstuk 4 (registratie/login) | T-03, T-04, T-05 (P FE-01) | Klaar |
| FE-02 | Inloggen en uitloggen | Inloggen met e-mail en wachtwoord, blokkade na 5 mislukte pogingen, uitloggen | `AuthController::login` en `logout`, `LoginAttemptRepository` | TO FE-02, hoofdstuk 4 (registratie/login) | T-03, T-04, T-05 (P FE-01) | Klaar |
| FE-03 | Eigen gegevens veilig beheren | Profielpagina: naam en e-mail wijzigen, wachtwoord wijzigen met je huidige wachtwoord, account met alle gegevens verwijderen | `ProfileController`, `profile/edit.php` | Geen aparte eis in het TO, wel hoofdstuk 9 (beveiliging en privacy) | Geen aparte taak, zie Verschillen | Klaar |
| FE-04 | Inkomst of uitgave met datum en categorie registreren | Transacties toevoegen, wijzigen en verwijderen. Of het een inkomst of uitgave is, volgt uit de categorie | `TransactionController`, `TransactionRepository`, `partials/forms/transaction.php` | TO FE-03, FE-04 en FE-05, wireframe "Nieuwe transactie" (hoofdstuk 5), flow (hoofdstuk 7), ERD (hoofdstuk 6) | T-06 t/m T-14 (P FE-02, FE-03, FE-04) | Klaar |
| FE-05 | Transacties per maand en categorie filteren | Maandfilter met vorige en volgende maand, filter op soort en categorie, snelfilter met categorielabels | `TransactionController::index`, `partials/month-filter.php` | Geen aparte eis in het TO, wel het scherm Transacties (hoofdstuk 4) | T-24, T-25, T-26 (P FE-08) | Klaar |
| FE-06 | Inkomsten en uitgaven per maand optellen | Dashboard met totalen en saldo van de maand, en totalen boven de gefilterde lijst | `TransactionRepository::totalsForMonth`, `DashboardController` | TO FE-06, wireframe "Dashboard" (hoofdstuk 5) | T-18, T-19, T-20 (P FE-06) | Klaar |
| FE-07 | Eigen categorieën beheren | Categorieën toevoegen, wijzigen en verwijderen, met een maandlimiet bij uitgaven | `CategoryController`, `CategoryRepository` | TO FE-05, ERD categories (hoofdstuk 6) | T-15, T-16, T-17 (P FE-05) | Klaar |
| FE-08 | Spaardoelen beheren | Spaardoelen toevoegen, wijzigen en verwijderen, geld toevoegen, voortgangsbalk | `SavingsGoalController`, `partials/goal-card.php` | TO FE-07, ERD saving_goals (hoofdstuk 6) | T-21, T-22, T-23 (P FE-07) | Klaar |
| FE-09 | Waarschuwen bij overschrijding, zonder advies | Melding op het dashboard en na het opslaan van een uitgave, met de tekst "geen financieel advies", en balken met de status van elke limiet | `BudgetService`, `partials/budget-bar.php` | Niet in het TO, zie Verschillen | Geen aparte taak, zie Verschillen | Klaar |
| FE-10 | Contentbeheerder beheert categorievoorstellen | Voorstellen toevoegen, wijzigen, aan- en uitzetten en verwijderen. Gebruikers kunnen een voorstel overnemen | `Content\SuggestionController`, `CategoryController::adopt` | TO FE-08 (admin beheert categorieën), aangepast, zie Verschillen | Geen aparte taak, zie Verschillen | Klaar |
| FE-11 | Contentbeheerder publiceert leerteksten | Leerteksten toevoegen, wijzigen en verwijderen, als concept of gepubliceerd. Gebruikers zien ze bij Tips en op het dashboard | `Content\TipController`, `TipController` | Niet in het TO, zie Verschillen | Geen aparte taak, zie Verschillen | Klaar |
| FE-12 | Contentbeheerder ziet anonieme aantallen | Aantal gebruikers, transacties en spaardoelen, activiteit per maand en hoe vaak voorstellen zijn overgenomen. Geen namen of bedragen | `StatisticsService`, `StatisticsRepository` | TO FE-08 (admin bekijkt gebruikers), aangepast tot alleen aantallen, zie Verschillen | Geen aparte taak, zie Verschillen | Klaar |

## Technische eisen

| ID | Eis | Hoe ik het heb opgelost |
|---|---|---|
| TE-01 | PHP 8 of hoger met MySQL of MariaDB | PHP 8.2 en MariaDB 10.4 (XAMPP), en de database van Plesk |
| TE-02 | Werkt op telefoon, tablet en computer | De CSS is eerst voor mobiel gemaakt. Op een klein scherm worden tabellen kaartjes en klapt het menu in |
| TE-03 | Iedere rol ziet alleen wat bij die rol hoort | Elke route heeft middleware (`auth`, `role:user` of `role:content_manager`), zie `routes/web.php` |
| TE-04 | Wachtwoorden veilig opslaan | Met `password_hash` en `password_verify` (bcrypt) |
| TE-05 | Invoer controleren en veilig tonen | De `Validator` geeft per veld een Nederlandse foutmelding. Alles wat op de pagina komt, gaat door `e()`, en er is een Content-Security-Policy |
| TE-06 | Bedragen als centen of als precies decimaal getal | Bedragen staan als hele centen in de database (`INT UNSIGNED`) en ik reken met de klasse `Money`, zonder kommagetallen |
| TE-07 | Een gebruiker ziet alleen zijn eigen gegevens | Elke query in de repositories filtert op `user_id`. Wie het id van iemand anders invult, krijgt 404 |
| TE-08 | Duidelijke meldingen | Meldingen voor gelukt, fout, let op en info, met kleur en tekst. Ook een melding bij een lege lijst en foutpagina's voor 403, 404, 419 en 500 |
| TE-09 | Code in duidelijke onderdelen | Mappen Core, Controllers, Repositories, Services, Support en Views |

## Verschillen tussen opdracht, ontwerp en product

| Onderdeel | Wat er anders is | Waarom |
|---|---|---|
| "Veilige databaseacties" in de opdracht | Dit heb ik opgevat als transacties (inkomsten en uitgaven) | De zin "veilige databaseacties per maand en categorie filteren" past eigenlijk alleen bij transacties. Dit is mijn eigen uitleg van de opdracht. |
| Framework (TE-01 in mijn TO en planning) | Ik heb gewone PHP 8 gebruikt met een eigen MVC-structuur, en geen Laravel | De opdracht staat Laravel toe, maar het hoeft niet. Zonder framework draait de app op Plesk zonder extra pakketten op de server, en ik kan elk onderdeel zelf uitleggen. De opbouw lijkt wel op Laravel: routes, dan controllers, dan repositories en services, en dan views. |
| Database opbouwen (TE-02 in mijn TO) | `database/schema.sql` en `seed.sql` in plaats van Laravel-migrations | Zonder Laravel heb ik geen migrations. Een SQL-bestand kan ik in phpMyAdmin in één keer importeren, lokaal en op Plesk. De foreign keys zitten er wel in, zoals ik had ontworpen. |
| Admin (FE-08 en hoofdstuk 4 in mijn TO) | Er is een contentbeheerder. Die beheert categorievoorstellen en leerteksten en ziet alleen aantallen. Hij kan geen gebruikersaccounts beheren | In de opdracht van MoneyMinds staat een contentbeheerder. Accounts en geldgegevens van gebruikers blijven zo privé. |
| Categorieën (ERD in mijn TO: één tabel met alleen een naam) | Elke gebruiker heeft eigen categorieën met een soort en een maandlimiet. Daarnaast is er een tabel `category_suggestions` met voorstellen | De opdracht vraagt dat gebruikers hun eigen categorieën beheren (FE-07), en voor de waarschuwing (FE-09) is een limiet per categorie nodig. |
| Bedragen (hoofdstuk 9 in mijn TO: decimal) | Bedragen staan als hele centen in de database | Met hele getallen kan er geen afrondingsfout ontstaan. Dit past ook bij TE-06 uit de opdracht. |
| De kolom `type` in transactions (ERD en hoofdstuk 8 in mijn TO) | Die kolom bestaat niet. Of iets een inkomst of uitgave is, volgt uit de categorie | Zo kan het niet gebeuren dat een uitgave in een inkomstencategorie staat. |
| Categorie verwijderen | Dat kan niet als er nog transacties in staan | Anders blijven er transacties zonder categorie over. |
| Soort van een categorie wijzigen | Dat kan niet als er al transacties in staan | Anders worden bestaande uitgaven ongemerkt inkomsten. |
| Spaardoelen (ERD in mijn TO: `current_amount`) | `saved_cents`, een streefdatum (niet verplicht) en een knop om geld toe te voegen | Zo hoeft de gebruiker niet steeds zelf het nieuwe totaal uit te rekenen. |
| Account van een contentbeheerder | Kan niet via registreren worden gemaakt en kan zichzelf niet verwijderen | Via registreren maak je alleen een gewoon account. Beheeraccounts maakt MoneyMinds zelf. |
| Statistieken | Alleen aantallen, geen bedragen | Vanwege privacy mag de contentbeheerder geen geldgegevens van gebruikers zien. |
| FE-03, FE-09, FE-11 en FE-12 | Wel gebouwd, maar ze stonden niet als eis in mijn TO en planning | Ze staan wel in de functionele eisen van de opdracht. Ik heb ze tijdens het bouwen toegevoegd. |
| Maandfilter (FE-08 in mijn planning) | Je kunt ook op categorie en op soort filteren (FE-05) | De opdracht vraagt filteren per maand en per categorie. |
| Tests (TE-05 in mijn planning: Laravel en PHPUnit) | PHPUnit 9.6 zonder Laravel, met een eigen testdatabase | Zie het testrapport. |

## Handmatig getest

Voordat ik de automatische tests had, heb ik de app getest door verzoeken naar een lokale database te sturen (63 controles, allemaal goed). Een paar voorbeelden:

- Niet ingelogd en het dashboard openen: je wordt naar de inlogpagina gestuurd.
- Een formulier zonder CSRF-token versturen: status 419.
- Fout wachtwoord: een algemene melding, en na 5 pogingen een blokkade (429).
- Een gewone gebruiker opent `/content/statistics`: 403. Een contentbeheerder opent `/dashboard` of `/transactions`: ook 403.
- Een transactie of categorie van een andere gebruiker openen of verwijderen: 404.
- Een transactie opslaan in de categorie van een ander: een foutmelding bij het veld.
- Een fout bedrag (`12,345`) of een datum die niet bestaat (`2026-02-30`): meldingen bij de velden.
- `<script>` in een omschrijving: wordt als gewone tekst getoond.
- Een uitgave boven de limiet: een waarschuwing met "geen financieel advies".
- Een categorie met transacties verwijderen: een foutmelding en er is niets verwijderd.
- Registreren met een bestaand e-mailadres of een te zwak wachtwoord: een melding. Met goede gegevens worden de startcategorieën gemaakt.

De automatische tests (238 tests, 96 % coverage) staan in de map `tests/` en zijn beschreven in het testrapport.
