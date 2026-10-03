/*
 * SpendSmart - kleine verbeteringen. De app werkt ook zonder JavaScript.
 */
(function () {
    'use strict';

    document.documentElement.classList.add('js');

    // Mobiel menu openen en sluiten.
    var toggle = document.querySelector('.nav-toggle');
    var nav = document.getElementById('main-nav');

    if (toggle && nav) {
        toggle.addEventListener('click', function () {
            var isOpen = nav.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });
    }

    // Bevestiging vragen voor verwijderen.
    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!window.confirm(form.getAttribute('data-confirm'))) {
                event.preventDefault();
            }
        });
    });

    // Maandlimiet alleen tonen bij uitgavencategorieën.
    var budgetField = document.querySelector('[data-budget-field]');
    var typeInputs = document.querySelectorAll('[data-toggle-budget]');

    function updateBudgetField() {
        var checked = document.querySelector('[data-toggle-budget]:checked');
        budgetField.hidden = checked !== null && checked.value !== 'expense';
    }

    if (budgetField && typeInputs.length > 0) {
        typeInputs.forEach(function (input) {
            input.addEventListener('change', updateBudgetField);
        });
        updateBudgetField();
    }
})();
