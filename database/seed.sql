-- SpendSmart demodata (alleen oefengegevens, geen echte personen).
-- Importeer na schema.sql. Alle demo-accounts hebben het wachtwoord: Welkom123!
--
--   student@spendsmart.test   gebruiker (met voorbeeldgegevens)
--   sanne@spendsmart.test     tweede gebruiker (om afscherming van gegevens te testen)
--   content@spendsmart.test   contentbeheerder
--
-- Datums zijn relatief aan vandaag, zodat de huidige maand altijd gegevens bevat.

SET NAMES utf8mb4;

INSERT INTO users (id, name, email, password_hash, role) VALUES
(1, 'Charlie Content', 'content@spendsmart.test', '$2y$10$6IRdsj8cMsGQQuYAZMhzF.IF8KsANhkfWbCQpUM8ktBfoEa5F1iRS', 'content_manager'),
(2, 'Sam Student', 'student@spendsmart.test', '$2y$10$6IRdsj8cMsGQQuYAZMhzF.IF8KsANhkfWbCQpUM8ktBfoEa5F1iRS', 'user'),
(3, 'Sanne de Vries', 'sanne@spendsmart.test', '$2y$10$6IRdsj8cMsGQQuYAZMhzF.IF8KsANhkfWbCQpUM8ktBfoEa5F1iRS', 'user');

INSERT INTO category_suggestions (id, name, type, description, is_active, created_by) VALUES
(1, 'Bijbaan', 'income', 'Loon van een bijbaan of stage.', 1, 1),
(2, 'Studiefinanciering', 'income', 'Maandelijkse studiefinanciering of toelage.', 1, 1),
(3, 'Boodschappen', 'expense', 'Eten en drinken uit de supermarkt.', 1, 1),
(4, 'Vervoer', 'expense', 'OV, fiets, brandstof.', 1, 1),
(5, 'Uitgaan', 'expense', 'Eten buiten de deur, feestjes, bioscoop.', 1, 1),
(6, 'Abonnementen', 'expense', 'Telefoon, streaming, sportschool.', 1, 1),
(7, 'Studiekosten', 'expense', 'Boeken, licenties en schoolspullen.', 0, 1);

-- Categorieën van Sam (gebruiker 2)
INSERT INTO categories (id, user_id, suggestion_id, name, type, monthly_budget_cents) VALUES
(1, 2, 1, 'Bijbaan', 'income', NULL),
(2, 2, 2, 'Studiefinanciering', 'income', NULL),
(3, 2, 3, 'Boodschappen', 'expense', 20000),
(4, 2, 4, 'Vervoer', 'expense', 6000),
(5, 2, 5, 'Uitgaan', 'expense', 7500),
(6, 2, 6, 'Abonnementen', 'expense', 4000),
(7, 2, NULL, 'Cadeaus', 'expense', NULL);

-- Categorieën van Sanne (gebruiker 3)
INSERT INTO categories (id, user_id, suggestion_id, name, type, monthly_budget_cents) VALUES
(8, 3, 1, 'Bijbaan', 'income', NULL),
(9, 3, 3, 'Boodschappen', 'expense', 15000),
(10, 3, NULL, 'Huur', 'expense', 45000);

-- Transacties deze maand en vorige maand
INSERT INTO transactions (user_id, category_id, amount_cents, transaction_date, description) VALUES
(2, 1, 42500, DATE_FORMAT(CURDATE(), '%Y-%m-01'), 'Salaris bijbaan'),
(2, 2, 30000, DATE_FORMAT(CURDATE(), '%Y-%m-01'), 'Studiefinanciering'),
(2, 3, 6435,  DATE_FORMAT(CURDATE(), '%Y-%m-01') + INTERVAL 1 DAY, 'Weekboodschappen'),
(2, 3, 5210,  DATE_FORMAT(CURDATE(), '%Y-%m-01') + INTERVAL 7 DAY, 'Weekboodschappen'),
(2, 3, 4875,  DATE_FORMAT(CURDATE(), '%Y-%m-01') + INTERVAL 14 DAY, 'Weekboodschappen'),
(2, 4, 3600,  DATE_FORMAT(CURDATE(), '%Y-%m-01') + INTERVAL 2 DAY, 'OV-saldo opgeladen'),
(2, 5, 4250,  DATE_FORMAT(CURDATE(), '%Y-%m-01') + INTERVAL 5 DAY, 'Verjaardag vriend'),
(2, 5, 3990,  DATE_FORMAT(CURDATE(), '%Y-%m-01') + INTERVAL 12 DAY, 'Bioscoop en eten'),
(2, 6, 1799,  DATE_FORMAT(CURDATE(), '%Y-%m-01') + INTERVAL 3 DAY, 'Telefoonabonnement'),
(2, 6, 1399,  DATE_FORMAT(CURDATE(), '%Y-%m-01') + INTERVAL 3 DAY, 'Streamingdienst'),
(2, 1, 39800, DATE_FORMAT(CURDATE() - INTERVAL 1 MONTH, '%Y-%m-01'), 'Salaris bijbaan'),
(2, 2, 30000, DATE_FORMAT(CURDATE() - INTERVAL 1 MONTH, '%Y-%m-01'), 'Studiefinanciering'),
(2, 3, 18950, DATE_FORMAT(CURDATE() - INTERVAL 1 MONTH, '%Y-%m-01') + INTERVAL 10 DAY, 'Boodschappen'),
(2, 4, 5200,  DATE_FORMAT(CURDATE() - INTERVAL 1 MONTH, '%Y-%m-01') + INTERVAL 4 DAY, 'Treinkaartjes'),
(2, 7, 2500,  DATE_FORMAT(CURDATE() - INTERVAL 1 MONTH, '%Y-%m-01') + INTERVAL 20 DAY, 'Cadeau zus'),
(3, 8, 55000, DATE_FORMAT(CURDATE(), '%Y-%m-01'), 'Salaris'),
(3, 9, 8120,  DATE_FORMAT(CURDATE(), '%Y-%m-01') + INTERVAL 3 DAY, 'Boodschappen'),
(3, 10, 45000, DATE_FORMAT(CURDATE(), '%Y-%m-01') + INTERVAL 1 DAY, 'Huur kamer');

INSERT INTO savings_goals (user_id, name, target_cents, saved_cents, target_date) VALUES
(2, 'Nieuwe laptop', 90000, 34500, CURDATE() + INTERVAL 5 MONTH),
(2, 'Rijlessen', 150000, 150000, NULL),
(2, 'Vakantie zomer', 60000, 8000, CURDATE() + INTERVAL 9 MONTH),
(3, 'Buffer', 100000, 25000, NULL);

INSERT INTO tips (title, body, is_published, published_at, author_id) VALUES
('Wat is het 50/30/20-idee?', 'Een veelgebruikt voorbeeld om over je geld na te denken: een deel voor vaste lasten, een deel voor wensen en een deel om te sparen. Het is een denkmodel om mee te oefenen, geen regel die voor iedereen klopt.', 1, NOW(), 1),
('Vaste en variabele uitgaven', 'Vaste uitgaven zijn elke maand ongeveer even hoog, zoals een abonnement. Variabele uitgaven wisselen, zoals boodschappen of uitgaan. Door ze apart te bekijken zie je sneller waar je geld naartoe gaat.', 1, NOW(), 1),
('Kleine bedragen tellen op', 'Een drankje van € 3,50 lijkt weinig. Tien keer per maand is dat € 35,00. Door ook kleine uitgaven te noteren, krijg je een eerlijk beeld van je maand.', 1, NOW(), 1),
('Concept: sparen met een doel', 'Deze tekst is nog in bewerking.', 0, NULL, 1);
