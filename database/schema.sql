-- SpendSmart databaseschema
-- Alle bedragen staan in hele centen (INT UNSIGNED), nooit als FLOAT.
-- Gebruik: importeer dit bestand in een lege database (phpMyAdmin of mysql-client).

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS login_attempts;
DROP TABLE IF EXISTS transactions;
DROP TABLE IF EXISTS savings_goals;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS category_suggestions;
DROP TABLE IF EXISTS tips;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE users (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name            VARCHAR(100) NOT NULL,
    email           VARCHAR(190) NOT NULL,
    password_hash   VARCHAR(255) NOT NULL,
    role            ENUM('user', 'content_manager') NOT NULL DEFAULT 'user',
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY users_email_unique (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Algemene categorievoorstellen, beheerd door de contentbeheerder.
CREATE TABLE category_suggestions (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name            VARCHAR(60) NOT NULL,
    type            ENUM('income', 'expense') NOT NULL,
    description     VARCHAR(255) NULL,
    is_active       TINYINT(1) NOT NULL DEFAULT 1,
    created_by      INT UNSIGNED NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY category_suggestions_name_type_unique (name, type),
    CONSTRAINT category_suggestions_created_by_fk FOREIGN KEY (created_by)
        REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Eigen categorieën van een gebruiker (eventueel overgenomen van een voorstel).
CREATE TABLE categories (
    id                      INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id                 INT UNSIGNED NOT NULL,
    suggestion_id           INT UNSIGNED NULL,
    name                    VARCHAR(60) NOT NULL,
    type                    ENUM('income', 'expense') NOT NULL,
    monthly_budget_cents    INT UNSIGNED NULL,
    created_at              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY categories_user_name_type_unique (user_id, name, type),
    KEY categories_suggestion_id_index (suggestion_id),
    CONSTRAINT categories_user_fk FOREIGN KEY (user_id)
        REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT categories_suggestion_fk FOREIGN KEY (suggestion_id)
        REFERENCES category_suggestions (id) ON DELETE SET NULL,
    CONSTRAINT categories_budget_check CHECK (monthly_budget_cents IS NULL OR monthly_budget_cents <= 999999999)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inkomsten en uitgaven. Het type (inkomst/uitgave) volgt uit de categorie.
CREATE TABLE transactions (
    id                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id             INT UNSIGNED NOT NULL,
    category_id         INT UNSIGNED NOT NULL,
    amount_cents        INT UNSIGNED NOT NULL,
    transaction_date    DATE NOT NULL,
    description         VARCHAR(255) NULL,
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY transactions_user_date_index (user_id, transaction_date),
    KEY transactions_category_index (category_id),
    CONSTRAINT transactions_user_fk FOREIGN KEY (user_id)
        REFERENCES users (id) ON DELETE CASCADE,
    -- RESTRICT: een categorie met transacties kan niet per ongeluk worden verwijderd.
    CONSTRAINT transactions_category_fk FOREIGN KEY (category_id)
        REFERENCES categories (id) ON DELETE RESTRICT,
    CONSTRAINT transactions_amount_check CHECK (amount_cents > 0 AND amount_cents <= 999999999)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE savings_goals (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id         INT UNSIGNED NOT NULL,
    name            VARCHAR(100) NOT NULL,
    target_cents    INT UNSIGNED NOT NULL,
    saved_cents     INT UNSIGNED NOT NULL DEFAULT 0,
    target_date     DATE NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY savings_goals_user_index (user_id),
    CONSTRAINT savings_goals_user_fk FOREIGN KEY (user_id)
        REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT savings_goals_amounts_check CHECK (
        target_cents > 0 AND target_cents <= 999999999 AND saved_cents <= 999999999
    )
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Algemene, niet-persoonlijke leerteksten.
CREATE TABLE tips (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    title           VARCHAR(150) NOT NULL,
    body            TEXT NOT NULL,
    is_published    TINYINT(1) NOT NULL DEFAULT 0,
    published_at    DATETIME NULL,
    author_id       INT UNSIGNED NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY tips_published_index (is_published, published_at),
    CONSTRAINT tips_author_fk FOREIGN KEY (author_id)
        REFERENCES users (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Mislukte inlogpogingen, om wachtwoorden raden (brute force) te beperken.
CREATE TABLE login_attempts (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    email           VARCHAR(190) NOT NULL,
    ip_address      VARCHAR(45) NOT NULL,
    attempted_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY login_attempts_lookup_index (email, ip_address, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
