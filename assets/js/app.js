'use strict';

document.addEventListener('DOMContentLoaded', function () {

    // ---- Payment portal: Cash / QR (gym membership + personal coach) ----
    document.querySelectorAll('[data-pay-portal]').forEach(function (portal) {
        var proof = portal.querySelector('input[type="file"]');

        function sync() {
            var checked = portal.querySelector('[data-pay-method]:checked');
            var method = checked ? checked.value : 'cash';
            portal.querySelectorAll('[data-pay-panel]').forEach(function (panel) {
                panel.hidden = panel.getAttribute('data-pay-panel') !== method;
            });
            portal.querySelectorAll('.pay-option').forEach(function (option) {
                var input = option.querySelector('[data-pay-method]');
                option.classList.toggle('selected', !!input && input.checked);
            });
            if (proof) { proof.required = method === 'qr'; }
        }

        portal.querySelectorAll('[data-pay-method]').forEach(function (radio) {
            radio.addEventListener('change', sync);
        });
        sync();
    });

    // ---- Gym membership: show the price of the chosen package in the portal ----
    document.querySelectorAll('[data-plan-price]').forEach(function (radio) {
        radio.addEventListener('change', function () {
            if (!radio.checked) { return; }
            document.querySelectorAll('[data-pay-amount]').forEach(function (target) {
                target.textContent = radio.getAttribute('data-plan-price');
            });
        });
    });

    // ---- Admin: 7-day coach availability helpers ----
    document.querySelectorAll('[data-schedule]').forEach(function (grid) {
        var rows = grid.querySelectorAll('.schedule-row:not(.schedule-head)');

        var all = grid.querySelector('[data-schedule-all]');
        if (all) {
            all.addEventListener('click', function () {
                rows.forEach(function (row) { row.querySelector('.sch-on').checked = true; });
            });
        }

        var copy = grid.querySelector('[data-schedule-copy]');
        if (copy) {
            copy.addEventListener('click', function () {
                if (!rows.length) { return; }
                var start = rows[0].querySelector('.sch-start').value;
                var end = rows[0].querySelector('.sch-end').value;
                rows.forEach(function (row) {
                    row.querySelector('.sch-start').value = start;
                    row.querySelector('.sch-end').value = end;
                });
            });
        }

        var clear = grid.querySelector('[data-schedule-clear]');
        if (clear) {
            clear.addEventListener('click', function () {
                rows.forEach(function (row) { row.querySelector('.sch-on').checked = false; });
            });
        }
    });
});
