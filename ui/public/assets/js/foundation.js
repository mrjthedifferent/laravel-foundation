/*!
 * Laravel Foundation admin UI
 * Sidebar, colour mode, direction, tooltips and Select2 wiring.
 * Requires Bootstrap 5 and jQuery. MIT License.
 */
(function () {
    'use strict';

    var root = document.documentElement;
    var THEME_KEY = 'fd-color-mode';
    var DIR_KEY = 'fd-direction';
    var SIDEBAR_KEY = 'fd-sidebar-mini';

    // ------------------------------------------------------------------
    // Colour mode: server default, overridden per browser from the theme panel
    // ------------------------------------------------------------------
    function systemMode() {
        return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }

    function applyColorMode(mode) {
        root.setAttribute('data-bs-theme', mode === 'auto' ? systemMode() : (mode === 'dark' ? 'dark' : 'light'));
    }

    function currentColorMode() {
        var stored = null;
        try { stored = localStorage.getItem(THEME_KEY); } catch (e) { /* storage blocked */ }

        return stored || (window.__THEME__ && window.__THEME__.colorMode) || 'light';
    }

    function applyDirection(dir) {
        var rtl = dir === 'rtl';
        root.setAttribute('dir', rtl ? 'rtl' : 'ltr');

        var sheet = document.getElementById('main-stylesheet');
        if (sheet && sheet.dataset.ltr && sheet.dataset.rtl) {
            var wanted = rtl ? sheet.dataset.rtl : sheet.dataset.ltr;
            if (sheet.getAttribute('href') !== wanted) {
                sheet.setAttribute('href', wanted);
            }
        }
    }

    // Applied immediately (this file loads in <head>) so the page never flashes the wrong mode.
    applyColorMode(currentColorMode());
    try {
        var storedDir = localStorage.getItem(DIR_KEY);
        if (storedDir) { applyDirection(storedDir); }
    } catch (e) { /* storage blocked */ }

    if (window.matchMedia) {
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function () {
            if (currentColorMode() === 'auto') { applyColorMode('auto'); }
        });
    }

    // ------------------------------------------------------------------
    // Sidebar
    // ------------------------------------------------------------------
    function initSidebar() {
        var sidebar = document.querySelector('.sidebar-main');
        if (!sidebar) { return; }

        try {
            if (localStorage.getItem(SIDEBAR_KEY) === '1') { sidebar.classList.add('sidebar-main-resized'); }
            if (localStorage.getItem(SIDEBAR_KEY) === '0') { sidebar.classList.remove('sidebar-main-resized'); }
        } catch (e) { /* storage blocked */ }

        document.querySelectorAll('.sidebar-main-resize').forEach(function (button) {
            button.addEventListener('click', function (event) {
                event.preventDefault();
                var mini = sidebar.classList.toggle('sidebar-main-resized');
                try { localStorage.setItem(SIDEBAR_KEY, mini ? '1' : '0'); } catch (e) { /* storage blocked */ }
            });
        });

        document.querySelectorAll('.sidebar-mobile-main-toggle').forEach(function (button) {
            button.addEventListener('click', function (event) {
                event.preventDefault();
                document.body.classList.toggle('sidebar-mobile-expanded');
            });
        });

        // A tap on the dimmed backdrop closes the mobile sidebar.
        document.addEventListener('click', function (event) {
            if (document.body.classList.contains('sidebar-mobile-expanded') && event.target === document.body) {
                document.body.classList.remove('sidebar-mobile-expanded');
            }
        });

        // Accordion submenus
        sidebar.querySelectorAll('.nav-item-submenu > .nav-link').forEach(function (link) {
            link.addEventListener('click', function (event) {
                event.preventDefault();

                if (sidebar.classList.contains('sidebar-main-resized') && window.innerWidth >= 992) {
                    sidebar.classList.remove('sidebar-main-resized');
                }

                var item = link.parentElement;
                var open = !item.classList.contains('nav-item-open');
                var nav = item.closest('.nav-sidebar');

                if (open && nav && nav.dataset.navType === 'accordion') {
                    Array.prototype.forEach.call(item.parentElement.children, function (sibling) {
                        if (sibling !== item && sibling.classList.contains('nav-item-open')) {
                            toggleSubmenu(sibling, false);
                        }
                    });
                }

                toggleSubmenu(item, open);
            });
        });
    }

    function toggleSubmenu(item, open) {
        var sub = item.querySelector(':scope > .nav-group-sub');
        item.classList.toggle('nav-item-open', open);
        if (sub) {
            sub.classList.toggle('show', open);
            sub.classList.toggle('collapse', !open);
        }
    }

    // ------------------------------------------------------------------
    // Theme panel (session overrides)
    // ------------------------------------------------------------------
    function initThemePanel() {
        var mode = currentColorMode();

        document.querySelectorAll('input[name="main-theme"]').forEach(function (input) {
            input.checked = input.value === mode;
            input.addEventListener('change', function () {
                try { localStorage.setItem(THEME_KEY, input.value); } catch (e) { /* storage blocked */ }
                applyColorMode(input.value);
            });
        });

        document.querySelectorAll('input[name="layout-direction"]').forEach(function (input) {
            input.checked = root.getAttribute('dir') === 'rtl';
            input.addEventListener('change', function () {
                var dir = input.checked ? 'rtl' : 'ltr';
                try { localStorage.setItem(DIR_KEY, dir); } catch (e) { /* storage blocked */ }
                applyDirection(dir);
            });
        });
    }

    // ------------------------------------------------------------------
    // Components
    // ------------------------------------------------------------------
    function initTooltips() {
        if (!window.bootstrap) { return; }

        document.querySelectorAll('[data-bs-popup="tooltip"], [data-bs-toggle="tooltip"]').forEach(function (el) {
            window.bootstrap.Tooltip.getOrCreateInstance(el);
        });
        document.querySelectorAll('[data-bs-popup="popover"], [data-bs-toggle="popover"]').forEach(function (el) {
            window.bootstrap.Popover.getOrCreateInstance(el);
        });
    }

    function initSelects(scope) {
        var $ = window.jQuery;
        if (!$ || !$.fn.select2) { return; }

        $(scope || document).find('select.select').each(function () {
            var $select = $(this);
            if ($select.hasClass('select2-hidden-accessible')) { return; }

            var $modal = $select.closest('.modal, .offcanvas');
            $select.select2({
                width: '100%',
                placeholder: $select.data('placeholder') || undefined,
                allowClear: Boolean($select.data('placeholder')) && !$select.prop('multiple') && !$select.prop('required'),
                minimumResultsForSearch: $select.data('minimum-results-for-search') !== undefined
                    ? $select.data('minimum-results-for-search') : 8,
                dropdownParent: $modal.length ? $modal : undefined
            });

            // Select2 renders its own combobox; without this it reaches a screen reader unnamed.
            var name = $select.attr('aria-label')
                || $('label[for="' + $select.attr('id') + '"]').first().text().trim()
                || $select.attr('name');
            if (name) {
                $select.next('.select2').find('.select2-selection__rendered').attr('aria-label', name);
            }
        });
    }

    // Remembers whether the index-page filter panel (#filter-collapse) is open.
    function initFilterMemory() {
        var filter = document.getElementById('filter-collapse');
        if (!filter) { return; }

        try {
            if (localStorage.getItem('filter-collapse') === 'open') { filter.classList.add('show'); }
            filter.addEventListener('shown.bs.collapse', function () { localStorage.setItem('filter-collapse', 'open'); });
            filter.addEventListener('hidden.bs.collapse', function () { localStorage.setItem('filter-collapse', 'closed'); });
        } catch (e) { /* storage blocked */ }
    }

    function ready(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback);
        } else {
            callback();
        }
    }

    ready(function () {
        initSidebar();
        initThemePanel();
        initTooltips();
        initSelects();
        initFilterMemory();

        document.addEventListener('shown.bs.modal', function (event) { initSelects(event.target); });
    });

    window.Foundation = {
        initSelects: initSelects,
        initTooltips: initTooltips,
        applyColorMode: applyColorMode,
        applyDirection: applyDirection
    };
})();
