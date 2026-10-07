# SpendSmart

Budgettool (MVP) voor MoneyMinds. Studenten registreren oefen-inkomsten en -uitgaven per maand en categorie, stellen eigen maandlimieten in en volgen spaardoelen. Een contentbeheerder beheert algemene tips en categorievoorstellen en ziet anonieme gebruiksaantallen.

> Alleen oefengegevens. Geen bankkoppeling, geen financieel advies.

## Techniek

- PHP 8.1+ (getest met 8.2), zonder framework; de app zelf heeft geen Composer-pakketten nodig (Composer alleen voor PHPUnit)
- MySQL / MariaDB (utf8mb4, InnoDB, foreign keys)
- Eigen MVC-structuur: een verzoek gaat van de router via de middleware naar een controller, die een repository of service gebruikt en daarna een view toont
- HTML/CSS/vanilla JS, responsive (mobile first), werkt ook zonder JavaScript

## Mappenstructuur

```
app/
  Core/            Router, Request, Middleware, Auth, Session, Csrf, Validator, View, Database, ErrorHandler
  Controllers/     Eén controller per onderdeel (Transaction, Category, SavingsGoal, Profile, ...)
  Controllers/Content/  Controllers voor de contentbeheerder
  Repositories/    Alle SQL (prepared statements), één repository per tabel
  Services/        Businesslogica: BudgetService, RegistrationService, StatisticsService
  Support/         Waardeobjecten/hulpklassen: Money (centen), Month, Role, CategoryType
  Views/           PHP-templates (layouts/, partials/, partials/forms/, per onderdeel een map)
  bootstrap.php    Autoloader, config en foutafhandeling
  helpers.php      e(), url(), money(), csrf_field(), ...
config/config.php  Instellingen (leest .env)
routes/web.php     Alle routes met hun toegangsregels
database/          schema.sql en seed.sql (demodata)
public/            Document root: index.php, .htaccess, assets/
docs/              Verantwoording (eisen ↔ code ↔ planning) en testrapport
storage/logs/      Foutlog
tests/             PHPUnit-tests: Unit/ (losse klassen) en Feature/ (integratie: een verzoek door de hele app, tot en met de database)
tools/             Hulpscripts voor het testen (build-pcov.sh)
```

## Lokaal starten (XAMPP)

1. Start **Apache** en **MySQL** in de XAMPP Control Panel (manager-osx).
2. Maak in phpMyAdmin (`http://localhost/phpmyadmin`) een database `spendsmart` met collatie `utf8mb4_unicode_ci`.
3. Importeer eerst `database/schema.sql`, daarna `database/seed.sql`.
4. Kopieer `.env.example` naar `.env` en controleer de databasegegevens.
5. Open `http://localhost/Examen%20portfolio/public/`.

### Demo-accounts (wachtwoord: `Welkom123!`)

| E-mail                     | Rol              |
|----------------------------|------------------|
| student@spendsmart.test    | Gebruiker (met voorbeeldgegevens) |
| sanne@spendsmart.test      | Gebruiker (om afscherming te testen) |
| content@spendsmart.test    | Contentbeheerder |

## Tests (PHPUnit)

De tests gebruiken een eigen database **`spendsmart_test`** (wordt automatisch aangemaakt). Je echte database `spendsmart` wordt nooit aangeraakt. MySQL in XAMPP moet aan staan.

Eenmalig installeren (in de Terminal, in de projectmap):

```
curl -sSL -o tools/composer.phar https://getcomposer.org/download/latest-stable/composer.phar
/Applications/XAMPP/xamppfiles/bin/php tools/composer.phar install
sh tools/build-pcov.sh
```

`build-pcov.sh` bouwt de coverage-driver PCOV voor de PHP van XAMPP (nodig voor het coverage-rapport; vereist `xcode-select --install`).

Tests draaien:

```
/Applications/XAMPP/xamppfiles/bin/php tools/composer.phar test
/Applications/XAMPP/xamppfiles/bin/php tools/composer.phar test:coverage
```

Het coverage-rapport staat daarna in `coverage/html/index.html` (openen in de browser) en `coverage/coverage.txt`. De uitleg en resultaten staan in [docs/testrapport.md](docs/testrapport.md).

Op de live server zijn `tests/`, `tools/` en `vendor/` niet nodig.

## Live zetten op Plesk

Zo heb ik de app op mijn eigen Plesk gezet: https://spendsmart.s2214593.jouw.website

1. **Database.** Ga in Plesk naar Databases en klik op Database toevoegen. Maak een database en een databasegebruiker (allebei `spendsmart`) met een sterk wachtwoord. Open phpMyAdmin en importeer eerst `database/schema.sql` en daarna `database/seed.sql`.
2. **Subdomein.** Ga naar Websites & domeinen en klik op Subdomein toevoegen. Ik heb `spendsmart` gekozen, met als document root `spendsmart/public`. Zo kan niemand via de browser bij `app/`, `config/`, `database/` of `.env`. Lukt het niet om de document root op `public` te zetten, dan vangt de `.htaccess` in de hoofdmap dit op.
3. **Bestanden.** Ga naar Bestanden, open de map `spendsmart`, upload een zip met de code en pak die daar uit.
4. **index.html weghalen.** Plesk zet zelf een `index.html` in `public`. Verwijder die, anders zie je de standaardpagina in plaats van SpendSmart.
5. **.env.** Maak in de map `spendsmart` een bestand `.env` (kijk naar `.env.example`) met de gegevens van de Plesk-database. Zet daar `APP_DEBUG=false` in.
6. **HTTPS.** Klik op het subdomein en kies SSL/TLS-certificaten. Haal daar een gratis certificaat op bij Let's Encrypt. De sessiecookie krijgt dan vanzelf `Secure`.
7. **PHP-versie.** Kies PHP 8.1 of hoger.
8. **Testen.** Log in met een demo-account en controleer dat bijvoorbeeld `/.env` niet te openen is.

## Beveiliging (samenvatting)

| Risico | Maatregel |
|---|---|
| Wachtwoorden lekken | `password_hash()` (bcrypt), nooit leesbaar opgeslagen; automatisch rehash |
| SQL-injectie | Alleen PDO prepared statements, `EMULATE_PREPARES=false` |
| XSS | Alle uitvoer via `e()` (`htmlspecialchars`), strenge Content-Security-Policy |
| CSRF | Token in elk formulier, gecontroleerd bij elk POST-verzoek |
| Toegang zonder rol | Middleware `auth` en `role:...` per route (403 bij verkeerde rol) |
| Andermans gegevens (IDOR) | Elke query filtert op `user_id`. Niet gevonden geeft 404 |
| Wachtwoord raden | Max. 5 mislukte pogingen per 15 min per e-mail + IP |
| Session fixation | Nieuw sessie-ID bij in-/uitloggen en wachtwoordwijziging; cookie `HttpOnly`, `SameSite=Lax` |
| Foutmeldingen lekken info | Technische details alleen in log; gebruiker ziet een nette foutpagina |

## Bedragen

Alle bedragen worden als **hele centen (INT)** opgeslagen en berekend (`App\Support\Money`). Er wordt nergens met `float` gerekend, dus geen afrondingsfouten. Invoer `12,50` of `12.50` wordt `1250`.
