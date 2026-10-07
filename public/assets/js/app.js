/*
 * SpendSmart - kleine verbeteringen. De app werkt ook zonder JavaScript.
 *
 * Dit bestand doet drie dingen:
 *   1. het menu op mobiel open- en dichtklappen
 *   2. bevestiging vragen voordat iets wordt verwijderd
 *   3. de maandlimiet verbergen bij een inkomstencategorie
 */
(function () {
    // Alles staat in een functie die meteen wordt uitgevoerd, zodat de variabelen niet "globaal" worden.
    'use strict';

    // Klasse 'js' op <html>: de CSS weet dan dat JavaScript werkt en mag het menu inklappen.
    // Zonder JavaScript blijft het menu gewoon open, zodat de site bruikbaar blijft.
    document.documentElement.classList.add('js');

    // 1. Mobiel menu openen en sluiten.
    var toggle = document.querySelector('.nav-toggle');
    var nav = document.getElementById('main-nav');

    if (toggle && nav) {
        toggle.addEventListener('click', function () {
            // toggle: klasse erbij als hij er niet is, eraf als hij er wel is. Geeft true als het menu nu open is.
            var isOpen = nav.classList.toggle('is-open');
            // Voor schermlezers: vertellen of het menu open of dicht is.
            toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
    }

    // 2. Bevestiging vragen voor verwijderen.
    // Elk formulier met data-confirm="..." toont eerst die vraag. Bij "Annuleren" wordt het formulier niet verstuurd.
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!window.confirm(form.getAttribute('data-confirm'))) {
                event.preventDefault(); // versturen tegenhouden
            }
        });
    });

    // 3. Maandlimiet alleen tonen bij uitgavencategorieën (formulier categorie toevoegen/wijzigen).
    var budgetField = document.querySelector('[data-budget-field]');
    var typeInputs = document.querySelectorAll('[data-toggle-budget]');

    function updateBudgetField() {
        // Welk keuzerondje (Inkomst/Uitgave) is aangeklikt? Bij Inkomst het limietveld verbergen.
        var checked = document.querySelector('[data-toggle-budget]:checked');
        budgetField.hidden = checked !== null && checked.value !== 'expense';
    }

    // Alleen uitvoeren als we op een pagina met dit formulier zijn.
    if (budgetField && typeInputs.length > 0) {
        typeInputs.forEach(function (input) {
            input.addEventListener('change', updateBudgetField);
        });
        updateBudgetField(); // ook meteen bij het laden van de pagina
    }
})();
