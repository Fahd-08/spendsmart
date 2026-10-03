# Verantwoording realisatie SpendSmart (KT1-W3)

Dit document koppelt elke gebouwde functie aan de eis, het ontwerp en de planning.
**Vul de kolommen "Ontwerp" en "Taak" aan met de nummers uit jouw eigen technisch ontwerp en planning.**

## Functionele eisen

| ID | Eis (opdracht) | Gebouwd als | Belangrijkste code | Ontwerp | Taak | Status |
|---|---|---|---|---|---|---|
| FE-01 | Account maken | Registratieformulier met naam, e-mail, wachtwoord (+ herhaling) en bevestiging "oefengegevens" | `AuthController::register`, `RegistrationService`, `auth/register.php` | | | Klaar |
| FE-02 | Inloggen/uitloggen | Inloggen met e-mail + wachtwoord, blokkade na 5 mislukte pogingen, uitloggen | `AuthController::login/logout`, `LoginAttemptRepository` | | | Klaar |
| FE-03 | Eigen gegevens veilig beheren | Profiel: naam/e-mail wijzigen, wachtwoord wijzigen (met huidig wachtwoord), account + alle gegevens verwijderen | `ProfileController`, `profile/edit.php` | | | Klaar |
| FE-04 | Inkomst of uitgave met datum en categorie registreren | Transactie toevoegen/wijzigen/verwijderen; soort volgt uit categorie | `TransactionController`, `TransactionRepository`, `partials/forms/transaction.php` | | | Klaar |
| FE-05 | Transacties per maand en categorie filteren | Maandfilter (vorige/volgende/kiezen), filter op soort en categorie, categoriechips | `TransactionController::index`, `partials/month-filter.php` | | | Klaar |
| FE-06 | Inkomsten en uitgaven per maand optellen | Dashboard met maandtotalen en saldo; totalen boven gefilterde lijst | `TransactionRepository::totalsForMonth`, `DashboardController` | | | Klaar |
| FE-07 | Eigen categorieën beheren | Categorieën toevoegen/wijzigen/verwijderen, maandlimiet per uitgavencategorie | `CategoryController`, `CategoryRepository` | | | Klaar |
| FE-08 | Spaardoelen beheren | Spaardoelen toevoegen/wijzigen/verwijderen, bedrag toevoegen, voortgangsbalk | `SavingsGoalController`, `partials/goal-card.php` | | | Klaar |
| FE-09 | Waarschuwen bij overschrijding zonder adviesclaim | Melding op dashboard en na opslaan van een uitgave, met tekst "geen financieel advies"; budgetbalken met status | `BudgetService`, `partials/budget-bar.php` | | | Klaar |
| FE-10 | Contentbeheerder: categorievoorstellen beheren | CRUD voorstellen, actief/inactief; gebruikers kunnen voorstellen overnemen | `Content\SuggestionController`, `CategoryController::adopt` | | | Klaar |
| FE-11 | Contentbeheerder: leerteksten publiceren | CRUD leerteksten, concept/gepubliceerd; gebruikers zien ze bij Tips en op dashboard | `Content\TipController`, `TipController` | | | Klaar |
| FE-12 | Contentbeheerder: anonieme gebruiksaantallen | Aantallen gebruikers, transacties, spaardoelen, activiteit per maand, gebruik van voorstellen (geen bedragen/namen) | `StatisticsService`, `StatisticsRepository` | | | Klaar |

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
| "Veilige databaseacties" in de opdracht | Opgevat als **transacties** (inkomsten en uitgaven) | De zin "veilige databaseacties per maand en categorie filteren" past alleen bij transacties; afgestemd met opdrachtgever *(invullen)* |
| Type van een transactie | Niet apart opgeslagen, maar afgeleid van de categorie | Voorkomt tegenstrijdige gegevens (bijv. uitgave in inkomstencategorie) |
| Categorie verwijderen | Kan niet als er nog transacties in staan | Voorkomt dat transacties zonder categorie achterblijven (betrouwbaarheid) |
| Soort van categorie wijzigen | Niet toegestaan als er transacties in staan | Anders veranderen bestaande uitgaven ongemerkt in inkomsten |
| Contentbeheerdersaccount | Kan niet via registratie worden aangemaakt en niet zelf worden verwijderd | Registratie maakt alleen gebruikers; beheeraccounts worden door MoneyMinds aangemaakt |
| Statistieken | Alleen aantallen, geen bedragen | Privacy: de contentbeheerder mag geen persoonlijke financiële gegevens zien |
| *(aanvullen)* | | |

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
