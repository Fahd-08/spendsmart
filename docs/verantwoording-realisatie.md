# Verantwoording realisatie SpendSmart (KT1-W3)

**Student:** Fahd El Fechka (2214593, klas 24A) · **Definitieve versie:** GitHub-tag v1.6

Dit document koppelt elke gebouwde functie aan de eis, het ontwerp en de planning.

- **ID en Eis** volgen de functionele eisen uit de opdracht van MoneyMinds. Die nummering wordt ook gebruikt in de code, de tests en het testrapport.
- **Ontwerp** verwijst naar mijn technisch ontwerp (*SpendSmart_Technisch_Ontwerp_Fahd.pdf*, "TO"): de eis-ID daarin en het hoofdstuk (§).
- **Taak** verwijst naar de taken in mijn planning (*SpendSmart_Planning_Fahd_El_Fechka.pdf*), met tussen haakjes de eis-ID uit de planning ("P").
- Mijn ontwerp en planning nummeren de eisen anders dan de opdracht. Bijvoorbeeld: in het TO is FE-03 "Inkomsten beheren", hier is dat onderdeel van FE-04. De koppeling staat in de tabel hieronder.

## Functionele eisen

| ID | Eis (opdracht) | Gebouwd als | Belangrijkste code | Ontwerp | Taak | Status |
|---|---|---|---|---|---|---|
| FE-01 | Account maken | Registratieformulier met naam, e-mail, wachtwoord (+ herhaling) en bevestiging "oefengegevens" | `AuthController::register`, `RegistrationService`, `auth/register.php` | TO FE-01; §4 Registratie/login | T-03, T-04, T-05 (P FE-01) | Klaar |
| FE-02 | Inloggen/uitloggen | Inloggen met e-mail + wachtwoord, blokkade na 5 mislukte pogingen, uitloggen | `AuthController::login/logout`, `LoginAttemptRepository` | TO FE-02; §4 Registratie/login | T-03, T-04, T-05 (P FE-01) | Klaar |
| FE-03 | Eigen gegevens veilig beheren | Profiel: naam/e-mail wijzigen, wachtwoord wijzigen (met huidig wachtwoord), account + alle gegevens verwijderen | `ProfileController`, `profile/edit.php` | TO §9 Beveiliging/privacy (geen aparte FE in TO) | Geen aparte taak; zie Verschillen | Klaar |
| FE-04 | Inkomst of uitgave met datum en categorie registreren | Transactie toevoegen/wijzigen/verwijderen; soort volgt uit categorie | `TransactionController`, `TransactionRepository`, `partials/forms/transaction.php` | TO FE-03, FE-04, FE-05; §5 wireframe "Nieuwe transactie"; §7 flow; §6 ERD transactions | T-06 t/m T-14 (P FE-02, FE-03, FE-04) | Klaar |
| FE-05 | Transacties per maand en categorie filteren | Maandfilter (vorige/volgende/kiezen), filter op soort en categorie, categoriechips | `TransactionController::index`, `partials/month-filter.php` | TO §4 Transacties (filter niet als aparte FE in TO) | T-24, T-25, T-26 (P FE-08) | Klaar |
| FE-06 | Inkomsten en uitgaven per maand optellen | Dashboard met maandtotalen en saldo; totalen boven gefilterde lijst | `TransactionRepository::totalsForMonth`, `DashboardController` | TO FE-06; §5 wireframe "Dashboard" | T-18, T-19, T-20 (P FE-06) | Klaar |
| FE-07 | Eigen categorieën beheren | Categorieën toevoegen/wijzigen/verwijderen, maandlimiet per uitgavencategorie | `CategoryController`, `CategoryRepository` | TO FE-05; §6 ERD categories | T-15, T-16, T-17 (P FE-05) | Klaar |
| FE-08 | Spaardoelen beheren | Spaardoelen toevoegen/wijzigen/verwijderen, bedrag toevoegen, voortgangsbalk | `SavingsGoalController`, `partials/goal-card.php` | TO FE-07; §6 ERD saving_goals | T-21, T-22, T-23 (P FE-07) | Klaar |
| FE-09 | Waarschuwen bij overschrijding zonder adviesclaim | Melding op dashboard en na opslaan van een uitgave, met tekst "geen financieel advies"; budgetbalken met status | `BudgetService`, `partials/budget-bar.php` | Niet in TO; zie Verschillen | Geen aparte taak; zie Verschillen | Klaar |
| FE-10 | Contentbeheerder: categorievoorstellen beheren | CRUD voorstellen, actief/inactief; gebruikers kunnen voorstellen overnemen | `Content\SuggestionController`, `CategoryController::adopt` | TO FE-08 (admin beheert categorieën), aangepast; zie Verschillen | Geen aparte taak; zie Verschillen | Klaar |
| FE-11 | Contentbeheerder: leerteksten publiceren | CRUD leerteksten, concept/gepubliceerd; gebruikers zien ze bij Tips en op dashboard | `Content\TipController`, `TipController` | Niet in TO; zie Verschillen | Geen aparte taak; zie Verschillen | Klaar |
| FE-12 | Contentbeheerder: anonieme gebruiksaantallen | Aantallen gebruikers, transacties, spaardoelen, activiteit per maand, gebruik van voorstellen (geen bedragen/namen) | `StatisticsService`, `StatisticsRepository` | TO FE-08 (admin bekijkt gebruikers), aangepast tot anonieme aantallen; zie Verschillen | Geen aparte taak; zie Verschillen | Klaar |

## Technische eisen

| ID | Eis | Invulling |
|---|---|---|
| TE-01 | PHP 8+ met MySQL/MariaDB | PHP 8.2, MariaDB 10.4 (XAMPP) / Plesk-database |
| TE-02 | Werkt op telefoon, tablet en computer | Mobile-first CSS, tabellen worden kaartjes < 700px, inklapbaar menu |
| TE-03 | Rollen: ieder ziet alleen wat bij de rol hoort | Middleware `auth` + `role:user` / `role:content_manager` per route (`routes/web.php`) |
| TE-04 | Wachtwoorden veilig opgeslagen | `password_hash` / `password_verify` (bcrypt) |
| TE-05 | Invoer controleren, veilig tonen | `Validator` met Nederlandse meldingen per veld; uitvoer via `e()`; CSP-header |
| TE-06 | Bedragen als centen of precies decimaal | `INT UNSIGNED` in centen + `Money`-klasse, geen floats |
| TE-07 | Gebruiker ziet alleen eigen gegevens | Elke query in repositories filtert op `user_id`; andermans ID → 404 |
| TE-08 | Duidelijke meldingen | Flash-meldingen (succes/fout/let op/info) met kleur én tekst, lege-lijstmeldingen, foutpagina's 403/404/419/500 |
| TE-09 | Code in duidelijke onderdelen | Core / Controllers / Repositories / Services / Support / Views |

## Verschillen tussen opdracht, ontwerp en product

| Onderdeel | Verschil | Reden |
|---|---|---|
| "Veilige databaseacties" in de opdracht | Opgevat als **transacties** (inkomsten en uitgaven) | De zin "veilige databaseacties per maand en categorie filteren" past alleen bij transacties. Dit is mijn eigen interpretatie van de opdracht. |
| Type van een transactie | Niet apart opgeslagen, maar afgeleid van de categorie | Voorkomt tegenstrijdige gegevens (bijv. uitgave in inkomstencategorie) |
| Categorie verwijderen | Kan niet als er nog transacties in staan | Voorkomt dat transacties zonder categorie achterblijven (betrouwbaarheid) |
| Soort van categorie wijzigen | Niet toegestaan als er transacties in staan | Anders veranderen bestaande uitgaven ongemerkt in inkomsten |
| Contentbeheerdersaccount | Kan niet via registratie worden aangemaakt en niet zelf worden verwijderd | Registratie maakt alleen gebruikers; beheeraccounts worden door MoneyMinds aangemaakt |
| Statistieken | Alleen aantallen, geen bedragen | Privacy: de contentbeheerder mag geen persoonlijke financiële gegevens zien |
| Framework (TO TE-01, planning TE-01) | **Gewone PHP 8 met een eigen MVC-structuur** in plaats van Laravel | De opdracht staat Laravel toe maar verplicht het niet. Zonder framework draait de app op Plesk zonder extra pakketten op de server, en kan ik elk onderdeel zelf uitleggen. De opbouw volgt hetzelfde idee als Laravel: routes → controllers → (repositories/services) → views. |
| Database opbouwen (TO TE-02) | `database/schema.sql` en `seed.sql` in plaats van Laravel-migrations | Zonder Laravel geen migrations. Eén SQL-bestand is in phpMyAdmin (lokaal en op Plesk) in één keer te importeren. Foreign keys staan er wel in, zoals ontworpen. |
| Admin (TO FE-08, §4 Admin) | Rol **contentbeheerder**: beheert categorievoorstellen en leerteksten en ziet anonieme aantallen. Geen beheer van gebruikersaccounts | De opdracht van MoneyMinds beschrijft een contentbeheerder. Gebruikersaccounts en persoonlijke financiële gegevens blijven privé (privacy). |
| Categorieën (TO §6 ERD: één tabel `categories` met alleen `name`) | Elke gebruiker heeft **eigen categorieën** (`user_id`, soort, maandlimiet), plus een aparte tabel `category_suggestions` met voorstellen | De opdracht vraagt om eigen categorieën beheren (FE-07) met een maandlimiet (nodig voor FE-09). |
| Bedragen (TO §9: decimal) | Opgeslagen als **hele centen** (`INT UNSIGNED`) | Geen afrondingsfouten, rekenen met gehele getallen; past bij de technische eis van de opdracht (TE-06). |
| Kolom `type` in `transactions` (TO §6 ERD, §8) | Niet opgeslagen; de soort volgt uit de categorie | Zie hierboven: zo kunnen soort en categorie elkaar nooit tegenspreken. |
| Spaardoelen (TO §6 ERD: `current_amount`) | `saved_cents`, optionele streefdatum en een knop "bedrag toevoegen" | Makkelijker in gebruik: de gebruiker telt een bedrag op in plaats van het totaal opnieuw in te typen. |
| Extra functies FE-03, FE-09, FE-11 en FE-12 | Gebouwd, maar niet als eis in TO en planning | Ze staan in de functionele eisen van de opdracht van MoneyMinds. Ze zijn tijdens de realisatie toegevoegd. |
| Maandfilter (planning FE-08) | Uitgebreid met filters op categorie en soort (FE-05) | De opdracht vraagt filteren per maand **en** categorie. |
| Tests (planning TE-05: Laravel/PHPUnit) | PHPUnit 9.6 zonder Laravel, met eigen testdatabase | Zie [testrapport.md](testrapport.md). |

## Handmatig geteste scenario's (rooktest)

Getest via HTTP tegen een lokale database (63 controles, allemaal geslaagd), onder andere:

- Niet ingelogd → dashboard geeft doorverwijzing naar inloggen
- Formulier zonder CSRF-token → 419
- Fout wachtwoord → algemene melding; na 5 pogingen → blokkade (429)
- Gebruiker opent `/content/statistics` → 403; contentbeheerder opent `/dashboard` of `/transactions` → 403
- Gebruiker opent/verwijdert transactie of categorie van een andere gebruiker → 404
- Transactie opslaan met categorie van een ander → foutmelding bij het veld
- Ongeldig bedrag (`12,345`) en ongeldige datum (`2026-02-30`) → meldingen bij de velden
- `<script>` in omschrijving → wordt als tekst getoond (ge-escaped)
- Uitgave boven limiet → waarschuwing "geen financieel advies"
- Categorie met transacties verwijderen → foutmelding, niets verwijderd
- Registreren met bestaand e-mailadres / te zwak wachtwoord → meldingen; geldig → startcategorieën aangemaakt

De geautomatiseerde tests (PHPUnit, 238 tests, 96 % coverage) staan in `tests/` en zijn beschreven in [testrapport.md](testrapport.md).
