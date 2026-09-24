# SpendSmart

Budgettool (MVP) voor MoneyMinds. Studenten registreren oefen-inkomsten en -uitgaven per maand en categorie, stellen eigen maandlimieten in en volgen spaardoelen. Een contentbeheerder beheert algemene tips en categorievoorstellen en ziet anonieme gebruiksaantallen.

> Alleen oefengegevens. Geen bankkoppeling, geen financieel advies.

## Techniek

- PHP 8.1+ (getest met 8.2), zonder framework en zonder Composer-afhankelijkheden
- MySQL / MariaDB (utf8mb4, InnoDB, foreign keys)
- Eigen lichte MVC-structuur: router → middleware → controller → repository/service → view
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
docs/              Verantwoording (eisen ↔ code ↔ planning)
storage/logs/      Foutlog
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

## Live zetten op Plesk

1. **Database**: Plesk → *Databases* → *Add Database* (bijv. `spendsmart`) met een eigen databasegebruiker en een sterk wachtwoord. Open phpMyAdmin via Plesk en importeer `database/schema.sql` en `database/seed.sql`.
2. **Bestanden**: upload alle bestanden naar `httpdocs/` (via *Files*, FTP, of de *Git*-functie van Plesk vanuit je GitHub-repository).
3. **Document root**: Plesk → *Hosting & DNS* → *Hosting* → zet *Document root* op `httpdocs/public`. Daardoor zijn `app/`, `config/`, `database/` en `.env` niet via de browser bereikbaar. (Lukt dat niet, dan vangt de `.htaccess` in de hoofdmap dit op.)
4. **PHP-versie**: Plesk → *PHP* → kies PHP 8.1 of hoger.
5. **.env**: maak in `httpdocs/` een bestand `.env` op basis van `.env.example`, met de Plesk-databasegegevens en **`APP_DEBUG=false`**.
6. **HTTPS**: Plesk → *SSL/TLS Certificates* → gratis Let's Encrypt-certificaat, en zet "Redirect from HTTP to HTTPS" aan. De sessiecookie wordt dan automatisch `Secure`.
7. Zorg dat `storage/logs/` schrijfbaar is voor PHP.
8. Test: inloggen met de demo-accounts, en controleer dat `https://jouw.website/.env` en `https://jouw.website/../config/config.php` niet op te vragen zijn.

## Beveiliging (samenvatting)

| Risico | Maatregel |
|---|---|
| Wachtwoorden lekken | `password_hash()` (bcrypt), nooit leesbaar opgeslagen; automatisch rehash |
| SQL-injectie | Alleen PDO prepared statements, `EMULATE_PREPARES=false` |
| XSS | Alle uitvoer via `e()` (`htmlspecialchars`), strenge Content-Security-Policy |
| CSRF | Token in elk formulier, gecontroleerd bij elk POST-verzoek |
| Toegang zonder rol | Middleware `auth` en `role:...` per route (403 bij verkeerde rol) |
| Andermans gegevens (IDOR) | Elke query filtert op `user_id`; niet gevonden → 404 |
| Wachtwoord raden | Max. 5 mislukte pogingen per 15 min per e-mail + IP |
| Session fixation | Nieuw sessie-ID bij in-/uitloggen en wachtwoordwijziging; cookie `HttpOnly`, `SameSite=Lax` |
| Foutmeldingen lekken info | Technische details alleen in log; gebruiker ziet een nette foutpagina |

## Bedragen

Alle bedragen worden als **hele centen (INT)** opgeslagen en berekend (`App\Support\Money`). Er wordt nergens met `float` gerekend, dus geen afrondingsfouten. Invoer `12,50` of `12.50` wordt `1250`.
