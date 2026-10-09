/*!
 * Laravel Foundation admin UI
 * Sidebar, colour mode, direction, dashboard layout editor, overlay components (modal, offcanvas, dropdown, collapse,
 * tab, tooltip, popover, alert) and Select2 wiring. Select2 needs jQuery; nothing else does.
 * MIT License.
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
        root.setAttribute('data-theme', mode === 'auto' ? systemMode() : (mode === 'dark' ? 'dark' : 'light'));
    }

    function currentColorMode() {
        var stored = null;
        try { stored = localStorage.getItem(THEME_KEY); } catch (e) { /* storage blocked */ }

        return stored || (window.__THEME__ && window.__THEME__.colorMode) || 'light';
    }

    function applyDirection(dir) {
        var rtl = dir === 'rtl';
        root.setAttribute('dir', rtl ? 'rtl' : 'ltr');
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

    // ------------------------------------------------------------------
    // Overlay components. Same data-attribute API the markup always used, under data-fd-*:
    //   data-fd-toggle="modal|offcanvas|dropdown|collapse|tab|tooltip|popover"
    //   data-fd-target="#id"   data-fd-dismiss="modal|offcanvas|alert"
    // Each one dispatches bubbling CustomEvents: fd:modal-shown, fd:modal-hidden, fd:tab-shown,
    // fd:collapse-shown, fd:offcanvas-shown, fd:dropdown-shown and so on.
    // ------------------------------------------------------------------
    var components = {};
    var FADE_MS = 200;

    function emit(el, name, detail) {
        el.dispatchEvent(new CustomEvent('fd:' + name, { bubbles: true, detail: detail || {} }));
    }

    function targetOf(el) {
        var sel = el.getAttribute('data-fd-target') || el.getAttribute('href');
        if (!sel || sel === '#' || sel.charAt(0) !== '#') { return null; }
        try { return document.querySelector(sel); } catch (e) { return null; }
    }

    function reflow(el) { return el.offsetHeight; }

    // --- Modal -------------------------------------------------------
    var openModals = [];

    components.modal = function (el) {
        if (el._fdModal) { return el._fdModal; }
        var api = { el: el, backdrop: null, trigger: null };

        function backdropMode() { return el.getAttribute('data-fd-backdrop') || 'true'; }
        function keyboard() { return el.getAttribute('data-fd-keyboard') !== 'false'; }

        api.show = function (trigger) {
            if (el.classList.contains('show')) { return; }
            api.trigger = trigger || document.activeElement;
            var level = openModals.length;
            openModals.push(api);
            el.style.display = 'block';
            el.style.zIndex = String(1055 + level * 20);
            el.removeAttribute('aria-hidden');
            el.setAttribute('aria-modal', 'true');
            el.setAttribute('role', 'dialog');
            document.body.classList.add('modal-open');

            if (backdropMode() !== 'false') {
                api.backdrop = document.createElement('div');
                api.backdrop.className = 'modal-backdrop fade';
                api.backdrop.style.zIndex = String(1050 + level * 20);
                document.body.appendChild(api.backdrop);
                reflow(api.backdrop);
                api.backdrop.classList.add('show');
            }
            reflow(el);
            el.classList.add('show');
            emit(el, 'modal-show');
            setTimeout(function () {
                var focus = el.querySelector('[autofocus]') || el;
                if (focus.focus) { focus.focus({ preventScroll: true }); }
                emit(el, 'modal-shown');
            }, FADE_MS);
        };

        api.hide = function () {
            if (!el.classList.contains('show')) { return; }
            el.classList.remove('show');
            if (api.backdrop) { api.backdrop.classList.remove('show'); }
            emit(el, 'modal-hide');
            setTimeout(function () {
                el.style.display = 'none';
                el.style.zIndex = '';
                el.setAttribute('aria-hidden', 'true');
                el.removeAttribute('aria-modal');
                if (api.backdrop) { api.backdrop.remove(); api.backdrop = null; }
                openModals = openModals.filter(function (m) { return m !== api; });
                if (!openModals.length) { document.body.classList.remove('modal-open'); }
                if (api.trigger && api.trigger.focus && document.contains(api.trigger)) { api.trigger.focus({ preventScroll: true }); }
                emit(el, 'modal-hidden');
            }, FADE_MS);
        };

        api.toggle = function (trigger) { el.classList.contains('show') ? api.hide() : api.show(trigger); };

        // A click on the dimmed area closes it, unless the backdrop is static.
        el.addEventListener('mousedown', function (event) {
            if (event.target !== el) { return; }
            if (backdropMode() === 'static') {
                el.classList.add('modal-static');
                setTimeout(function () { el.classList.remove('modal-static'); }, 300);
            } else {
                api.hide();
            }
        });
        el._fdKeyboard = keyboard;
        el._fdModal = api;
        return api;
    };

    // --- Offcanvas ---------------------------------------------------
    components.offcanvas = function (el) {
        if (el._fdOffcanvas) { return el._fdOffcanvas; }
        var api = { el: el, backdrop: null, trigger: null };

        api.show = function (trigger) {
            if (el.classList.contains('show')) { return; }
            api.trigger = trigger || document.activeElement;
            if (el.getAttribute('data-fd-backdrop') !== 'false') {
                api.backdrop = document.createElement('div');
                api.backdrop.className = 'offcanvas-backdrop fade';
                document.body.appendChild(api.backdrop);
                reflow(api.backdrop);
                api.backdrop.classList.add('show');
                api.backdrop.addEventListener('click', api.hide);
            }
            el.setAttribute('aria-modal', 'true');
            el.setAttribute('role', 'dialog');
            el.classList.add('showing');
            reflow(el);
            el.classList.add('show');
            emit(el, 'offcanvas-show');
            setTimeout(function () {
                el.classList.remove('showing');
                el.focus && el.focus({ preventScroll: true });
                emit(el, 'offcanvas-shown');
            }, 300);
        };

        api.hide = function () {
            if (!el.classList.contains('show')) { return; }
            el.classList.remove('show');
            if (api.backdrop) { api.backdrop.classList.remove('show'); }
            emit(el, 'offcanvas-hide');
            setTimeout(function () {
                el.removeAttribute('aria-modal');
                if (api.backdrop) { api.backdrop.remove(); api.backdrop = null; }
                if (api.trigger && api.trigger.focus && document.contains(api.trigger)) { api.trigger.focus({ preventScroll: true }); }
                emit(el, 'offcanvas-hidden');
            }, 300);
        };

        api.toggle = function (trigger) { el.classList.contains('show') ? api.hide() : api.show(trigger); };
        el._fdOffcanvas = api;
        return api;
    };

    // --- Dropdown ----------------------------------------------------
    function dropdownMenu(toggle) {
        // While open, the menu lives on <body> (see below), so it is remembered rather than found.
        if (toggle._fdMenu) { return toggle._fdMenu; }
        var next = toggle.nextElementSibling;
        if (next && next.classList.contains('dropdown-menu')) { return next; }
        var parent = toggle.closest('.dropdown, .dropup, .dropend, .dropstart') || toggle.parentElement;
        return parent ? parent.querySelector('.dropdown-menu') : null;
    }

    function placeMenu(toggle, menu) {
        var rect = toggle.getBoundingClientRect();
        menu.setAttribute('data-placed', '');
        menu.style.top = '0px';
        menu.style.left = '0px';
        var w = menu.offsetWidth;
        var h = menu.offsetHeight;
        var gap = 4;
        var rtl = root.getAttribute('dir') === 'rtl';
        var alignEnd = menu.classList.contains('dropdown-menu-end') !== rtl;
        var left = alignEnd ? rect.right - w : rect.left;
        left = Math.max(8, Math.min(left, window.innerWidth - w - 8));
        var top = rect.bottom + gap;
        var up = menu.parentElement && menu.parentElement.classList.contains('dropup');
        if (up || (top + h > window.innerHeight - 8 && rect.top - gap - h > 8)) { top = rect.top - gap - h; }
        menu.style.left = Math.round(left) + 'px';
        menu.style.top = Math.round(Math.max(8, top)) + 'px';
    }

    var openDropdown = null;

    components.dropdown = function (toggle) {
        if (toggle._fdDropdown) { return toggle._fdDropdown; }
        var api = { toggle: toggle };
        toggle._fdDropdown = api;
        api.menu = function () { return dropdownMenu(toggle); };
        api.isOpen = function () { var m = api.menu(); return !!m && m.classList.contains('show'); };
        api.show = function () {
            var menu = api.menu();
            if (!menu || api.isOpen()) { return; }
            if (openDropdown) { openDropdown.hide(); }
            // Moved to <body> while open: an ancestor with backdrop-filter (the navbar) or a transform
            // would otherwise become the containing block of this fixed menu and offset it.
            toggle._fdMenu = menu;
            toggle._fdHome = { parent: menu.parentNode, next: menu.nextSibling };
            document.body.appendChild(menu);
            menu.classList.add('show');
            toggle.classList.add('show');
            toggle.setAttribute('aria-expanded', 'true');
            placeMenu(toggle, menu);
            openDropdown = api;
            emit(toggle, 'dropdown-shown');
        };
        api.hide = function () {
            var menu = api.menu();
            if (!menu) { return; }
            menu.classList.remove('show');
            menu.removeAttribute('data-placed');
            menu.style.top = menu.style.left = '';
            if (toggle._fdHome && toggle._fdHome.parent) {
                toggle._fdHome.parent.insertBefore(menu, toggle._fdHome.next && toggle._fdHome.next.parentNode === toggle._fdHome.parent ? toggle._fdHome.next : null);
            }
            toggle._fdMenu = null;
            toggle._fdHome = null;
            toggle.classList.remove('show');
            toggle.setAttribute('aria-expanded', 'false');
            if (openDropdown === api) { openDropdown = null; }
            emit(toggle, 'dropdown-hidden');
        };
        api.toggleMenu = function () { api.isOpen() ? api.hide() : api.show(); };
        return api;
    };

    // --- Collapse ----------------------------------------------------
    components.collapse = function (el) {
        var api = { el: el };
        function sync(open) {
            document.querySelectorAll('[data-fd-toggle="collapse"]').forEach(function (t) {
                if (targetOf(t) === el) {
                    t.setAttribute('aria-expanded', open ? 'true' : 'false');
                    t.classList.toggle('collapsed', !open);
                }
            });
        }
        api.show = function () {
            if (el.classList.contains('show')) { return; }
            el.classList.add('show');
            sync(true);
            emit(el, 'collapse-shown');
        };
        api.hide = function () {
            if (!el.classList.contains('show')) { return; }
            el.classList.remove('show');
            sync(false);
            emit(el, 'collapse-hidden');
        };
        api.toggle = function () { el.classList.contains('show') ? api.hide() : api.show(); };
        return api;
    };

    // --- Tabs and pills ----------------------------------------------
    components.tab = function (link) {
        var api = { link: link };
        api.show = function () {
            if (link.classList.contains('active')) { return; }
            var pane = targetOf(link);
            if (!pane) { return; }
            var nav = link.closest('.nav, .list-group, [role="tablist"]');
            var previous = nav ? nav.querySelector('[data-fd-toggle="tab"].active') : null;
            if (previous) {
                previous.classList.remove('active');
                previous.setAttribute('aria-selected', 'false');
            }
            var content = pane.parentElement;
            Array.prototype.forEach.call(content.children, function (p) {
                if (p.classList.contains('tab-pane')) { p.classList.remove('active', 'show'); }
            });
            link.classList.add('active');
            link.setAttribute('aria-selected', 'true');
            pane.classList.add('active');
            reflow(pane);
            pane.classList.add('show');
            emit(link, 'tab-shown', { relatedTarget: previous });
        };
        return api;
    };

    // --- Tooltip and popover -----------------------------------------
    var floating = null;

    function clearFloating() {
        if (floating) { floating.remove(); floating = null; }
    }

    function sanitize(html) {
        var tpl = document.createElement('template');
        tpl.innerHTML = html;
        tpl.content.querySelectorAll('script, style, iframe, object, embed, link, meta').forEach(function (n) { n.remove(); });
        tpl.content.querySelectorAll('*').forEach(function (n) {
            Array.prototype.slice.call(n.attributes).forEach(function (a) {
                if (/^on/i.test(a.name) || (/^(href|src|xlink:href)$/i.test(a.name) && /^\s*javascript:/i.test(a.value))) {
                    n.removeAttribute(a.name);
                }
            });
        });
        return tpl.innerHTML;
    }

    function placeFloating(anchor, tip, placement) {
        var rect = anchor.getBoundingClientRect();
        var w = tip.offsetWidth, h = tip.offsetHeight, gap = 6;
        var spots = {
            top: [rect.left + rect.width / 2 - w / 2, rect.top - h - gap],
            bottom: [rect.left + rect.width / 2 - w / 2, rect.bottom + gap],
            left: [rect.left - w - gap, rect.top + rect.height / 2 - h / 2],
            right: [rect.right + gap, rect.top + rect.height / 2 - h / 2]
        };
        var flip = { top: 'bottom', bottom: 'top', left: 'right', right: 'left' };
        var p = spots[placement] ? placement : 'top';
        var pos = spots[p];
        if ((p === 'top' && pos[1] < 4) || (p === 'bottom' && pos[1] + h > window.innerHeight - 4)
            || (p === 'left' && pos[0] < 4) || (p === 'right' && pos[0] + w > window.innerWidth - 4)) {
            pos = spots[flip[p]];
        }
        tip.style.left = Math.round(Math.max(4, Math.min(pos[0], window.innerWidth - w - 4))) + 'px';
        tip.style.top = Math.round(Math.max(4, Math.min(pos[1], window.innerHeight - h - 4))) + 'px';
    }

    function showTooltip(el) {
        var text = el.getAttribute('data-fd-title') || el.getAttribute('title') || el.getAttribute('data-fd-original-title');
        if (!text) { return; }
        if (el.hasAttribute('title')) {
            el.setAttribute('data-fd-original-title', el.getAttribute('title'));
            el.removeAttribute('title');
        }
        clearFloating();
        floating = document.createElement('div');
        floating.className = 'tooltip';
        floating.setAttribute('role', 'tooltip');
        floating.textContent = text;
        document.body.appendChild(floating);
        placeFloating(el, floating, el.getAttribute('data-fd-placement') || 'top');
    }

    function showPopover(el) {
        var body = el.getAttribute('data-fd-content') || '';
        var title = el.getAttribute('data-fd-title') || el.getAttribute('data-fd-original-title') || '';
        clearFloating();
        floating = document.createElement('div');
        floating.className = 'popover';
        floating.setAttribute('role', 'dialog');
        floating._fdOwner = el;
        var header = document.createElement('div');
        header.className = 'popover-header';
        header.textContent = title;
        var content = document.createElement('div');
        content.className = 'popover-body';
        if (el.getAttribute('data-fd-html') === 'true') { content.innerHTML = sanitize(body); } else { content.textContent = body; }
        floating.appendChild(header);
        floating.appendChild(content);
        document.body.appendChild(floating);
        placeFloating(el, floating, el.getAttribute('data-fd-placement') || 'top');
    }

    // --- Delegated wiring --------------------------------------------
    function closeAllDropdowns() { if (openDropdown) { openDropdown.hide(); } }

    document.addEventListener('click', function (event) {
        var t = event.target;
        if (!(t instanceof Element)) { return; }

        var dismiss = t.closest('[data-fd-dismiss]');
        if (dismiss) {
            var kind = dismiss.getAttribute('data-fd-dismiss');
            if (kind === 'modal') { var m = dismiss.closest('.modal'); if (m) { components.modal(m).hide(); } }
            if (kind === 'offcanvas') { var o = dismiss.closest('.offcanvas'); if (o) { components.offcanvas(o).hide(); } }
            if (kind === 'alert') { var a = dismiss.closest('.alert'); if (a) { a.remove(); } }
            return;
        }

        var toggle = t.closest('[data-fd-toggle]');
        if (toggle) {
            var type = toggle.getAttribute('data-fd-toggle');
            var el = targetOf(toggle);
            if (type === 'modal' && el) { event.preventDefault(); components.modal(el).toggle(toggle); return; }
            if (type === 'offcanvas' && el) { event.preventDefault(); components.offcanvas(el).toggle(toggle); return; }
            if (type === 'collapse' && el) { event.preventDefault(); components.collapse(el).toggle(); return; }
            if (type === 'tab' && el) { event.preventDefault(); components.tab(toggle).show(); return; }
            if (type === 'dropdown') {
                event.preventDefault();
                components.dropdown(toggle).toggleMenu();
                return;
            }
            if (type === 'popover') {
                event.preventDefault();
                if (floating && floating._fdOwner === toggle) { clearFloating(); } else { showPopover(toggle); }
                return;
            }
            if (type === 'tooltip') { clearFloating(); return; }
        }

        // Outside click: close the open dropdown (unless it asked to stay for clicks inside) and any popover.
        if (openDropdown) {
            var menu = openDropdown.menu();
            var inside = menu && menu.contains(t);
            var mode = openDropdown.toggle.getAttribute('data-fd-auto-close');
            if (!inside || (mode !== 'outside' && t.closest('.dropdown-item'))) { openDropdown.hide(); }
        }
        if (floating && floating._fdOwner && !floating.contains(t)) { clearFloating(); }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            if (floating) { clearFloating(); }
            if (openDropdown) { var tg = openDropdown.toggle; openDropdown.hide(); tg.focus(); return; }
            var top = openModals[openModals.length - 1];
            if (top && top.el._fdKeyboard()) { top.hide(); return; }
            var oc = document.querySelector('.offcanvas.show');
            if (oc) { components.offcanvas(oc).hide(); }
        }
        // Keep Tab inside the open modal or drawer
        if (event.key === 'Tab') {
            var layer = openModals.length ? openModals[openModals.length - 1].el : document.querySelector('.offcanvas.show');
            if (layer) {
                var visible = focusables(layer);
                if (!visible.length) { event.preventDefault(); return; }
                var first = visible[0], last = visible[visible.length - 1];
                if (event.shiftKey && (document.activeElement === first || document.activeElement === layer)) { event.preventDefault(); last.focus(); }
                else if (!event.shiftKey && document.activeElement === last) { event.preventDefault(); first.focus(); }
            }
        }

        // Arrow keys move through an open dropdown menu; ArrowDown on a closed toggle opens it
        if (event.key === 'ArrowDown' || event.key === 'ArrowUp' || event.key === 'Home' || event.key === 'End') {
            var target = event.target instanceof Element ? event.target : null;
            var toggleEl = target ? target.closest('[data-fd-toggle="dropdown"]') : null;

            if (toggleEl && !components.dropdown(toggleEl).isOpen() && event.key === 'ArrowDown') {
                event.preventDefault();
                components.dropdown(toggleEl).show();
                var firstItem = menuItems(components.dropdown(toggleEl).menu())[0];
                if (firstItem) { firstItem.focus(); }
                return;
            }

            if (openDropdown) {
                var items = menuItems(openDropdown.menu());
                if (!items.length) { return; }
                event.preventDefault();
                var at = items.indexOf(document.activeElement);
                var next = event.key === 'Home' ? 0
                    : event.key === 'End' ? items.length - 1
                    : event.key === 'ArrowDown' ? (at + 1) % items.length
                    : (at <= 0 ? items.length - 1 : at - 1);
                items[next].focus();
            }
        }
    });

    function focusables(root) {
        var found = root.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])');
        return Array.prototype.filter.call(found, function (n) { return n.offsetParent !== null || n === document.activeElement; });
    }

    function menuItems(menu) {
        return menu ? Array.prototype.slice.call(menu.querySelectorAll('.dropdown-item:not(.disabled):not(:disabled)')) : [];
    }

    function tooltipTarget(event) {
        return event.target instanceof Element ? event.target.closest('[data-fd-toggle="tooltip"]') : null;
    }
    document.addEventListener('mouseover', function (event) { var el = tooltipTarget(event); if (el) { showTooltip(el); } });
    document.addEventListener('focusin', function (event) { var el = tooltipTarget(event); if (el) { showTooltip(el); } });
    document.addEventListener('mouseout', function (event) { if (tooltipTarget(event) && floating && !floating._fdOwner) { clearFloating(); } });
    document.addEventListener('focusout', function (event) { if (tooltipTarget(event) && floating && !floating._fdOwner) { clearFloating(); } });
    window.addEventListener('resize', function () { closeAllDropdowns(); clearFloating(); });
    window.addEventListener('scroll', function (event) {
        // A scroll inside the open menu itself must not close it
        if (openDropdown && openDropdown.menu() && event.target instanceof Node && openDropdown.menu().contains(event.target)) { return; }
        closeAllDropdowns();
        clearFloating();
    }, true);

    // jQuery plugin shims, so `$('#id').modal('show')` and friends keep working.
    function installJqueryShims() {
        var $ = window.jQuery;
        if (!$ || !$.fn) { return; }
        function run(factory, defaultAction) {
            return function (action) {
                return this.each(function () {
                    var api = factory(this);
                    var name = typeof action === 'string' ? action : defaultAction;
                    if (typeof api[name] === 'function') { api[name](); }
                });
            };
        }
        $.fn.modal = run(components.modal, 'show');
        $.fn.offcanvas = run(components.offcanvas, 'show');
        $.fn.collapse = run(components.collapse, 'toggle');
        $.fn.tab = run(components.tab, 'show');
        $.fn.dropdown = function (action) {
            return this.each(function () {
                var api = components.dropdown(this);
                if (action === 'hide') { api.hide(); } else if (action === 'show') { api.show(); } else { api.toggleMenu(); }
            });
        };
    }

    // ------------------------------------------------------------------
    // Dashboard layout editor: hide, reorder (drag or buttons) and resize widgets,
    // then save the arrangement for the signed-in user.
    // ------------------------------------------------------------------
    function initDashboard() {
        var board = document.querySelector('[data-fd-dashboard]');
        if (!board) { return; }

        var WIDTHS = [3, 4, 6, 8, 12];
        var dragging = null;
        var dirty = false;

        function widgets() { return Array.prototype.slice.call(board.querySelectorAll('[data-fd-widget]')); }

        function csrf() {
            var meta = document.querySelector('meta[name="csrf-token"]');
            return meta ? meta.getAttribute('content') : '';
        }

        function setWidth(widget, width) {
            WIDTHS.forEach(function (w) { widget.classList.remove('fd-w-' + w); });
            widget.classList.add('fd-w-' + width);
            widget.setAttribute('data-width', String(width));
        }

        function request(method, url, body) {
            return fetch(url, {
                method: method,
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf(),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: body ? JSON.stringify(body) : undefined
            }).then(function (response) {
                if (!response.ok) { throw new Error('HTTP ' + response.status); }
                return response.json();
            });
        }

        function fail() {
            var message = board.getAttribute('data-msg-error') || 'Could not save the layout.';
            if (typeof window.toast === 'function') { window.toast('error', message, ''); } else { window.alert(message); }
        }

        function setEditing(on) {
            document.body.classList.toggle('is-editing', on);
            widgets().forEach(function (widget) {
                var handle = widget.querySelector('.fd-widget-handle');
                if (handle) { handle.setAttribute('draggable', on ? 'true' : 'false'); }
            });
        }

        function save() {
            if (!dirty) { setEditing(false); return Promise.resolve(); }

            var items = widgets().map(function (widget) {
                return {
                    key: widget.getAttribute('data-key'),
                    width: parseInt(widget.getAttribute('data-width'), 10),
                    hidden: widget.getAttribute('data-hidden') === 'true'
                };
            });

            return request('PUT', board.getAttribute('data-url-save'), { items: items })
                // Reload: a widget that was hidden has no markup to show until the server renders it.
                .then(function () { window.location.reload(); })
                .catch(fail);
        }

        function reset() {
            var go = function () {
                request('DELETE', board.getAttribute('data-url-reset'))
                    .then(function () { window.location.reload(); })
                    .catch(fail);
            };

            if (window.Swal) {
                window.Swal.fire({
                    title: board.getAttribute('data-msg-reset-title') || 'Reset layout?',
                    text: board.getAttribute('data-msg-reset-text') || '',
                    icon: 'warning',
                    showCancelButton: true
                }).then(function (result) { if (result.isConfirmed) { go(); } });
            } else {
                go();
            }
        }

        function move(widget, direction) {
            var sibling = direction < 0 ? widget.previousElementSibling : widget.nextElementSibling;
            while (sibling && !sibling.hasAttribute('data-fd-widget')) {
                sibling = direction < 0 ? sibling.previousElementSibling : sibling.nextElementSibling;
            }
            if (!sibling) { return; }

            if (direction < 0) { board.insertBefore(widget, sibling); } else { board.insertBefore(widget, sibling.nextElementSibling); }
            dirty = true;
            var focus = widget.querySelector('[data-fd-widget-act="' + (direction < 0 ? 'earlier' : 'later') + '"]');
            if (focus) { focus.focus(); }
        }

        function act(widget, action) {
            var width = parseInt(widget.getAttribute('data-width'), 10);
            var index = WIDTHS.indexOf(width);

            if (action === 'earlier') { move(widget, -1); return; }
            if (action === 'later') { move(widget, 1); return; }
            if (action === 'narrower' && index > 0) { setWidth(widget, WIDTHS[index - 1]); dirty = true; }
            if (action === 'wider' && index < WIDTHS.length - 1) { setWidth(widget, WIDTHS[index + 1]); dirty = true; }
            if (action === 'toggle') {
                var hidden = widget.getAttribute('data-hidden') !== 'true';
                widget.setAttribute('data-hidden', hidden ? 'true' : 'false');
                widget.classList.toggle('is-hidden', hidden);
                var icon = widget.querySelector('[data-fd-widget-act="toggle"] i');
                if (icon) { icon.className = hidden ? 'ph-eye-slash' : 'ph-eye'; }
                // Hidden ones stay on screen while editing, dimmed, so they can be brought back.
                widget.style.opacity = hidden ? '.55' : '';
                dirty = true;
            }
        }

        document.addEventListener('click', function (event) {
            var target = event.target instanceof Element ? event.target : null;
            if (!target) { return; }

            var control = target.closest('[data-fd-dash]');
            if (control) {
                var which = control.getAttribute('data-fd-dash');
                if (which === 'edit') { setEditing(true); }
                if (which === 'done') { save(); }
                if (which === 'reset') { reset(); }
                return;
            }

            var button = target.closest('[data-fd-widget-act]');
            if (button) {
                var widget = button.closest('[data-fd-widget]');
                if (widget) { act(widget, button.getAttribute('data-fd-widget-act')); }
            }
        });

        // Drag and drop by the handle: the widget under the pointer is where the dragged one lands.
        board.addEventListener('dragstart', function (event) {
            var handle = event.target instanceof Element ? event.target.closest('.fd-widget-handle') : null;
            if (!handle) { return; }
            dragging = handle.closest('[data-fd-widget]');
            if (!dragging) { return; }
            dragging.classList.add('is-dragging');
            event.dataTransfer.effectAllowed = 'move';
            event.dataTransfer.setData('text/plain', dragging.getAttribute('data-key'));
            if (event.dataTransfer.setDragImage) { event.dataTransfer.setDragImage(dragging, 20, 20); }
        });

        board.addEventListener('dragover', function (event) {
            if (!dragging) { return; }
            var over = event.target instanceof Element ? event.target.closest('[data-fd-widget]') : null;
            if (!over || over === dragging) { return; }
            event.preventDefault();
            widgets().forEach(function (w) { w.classList.toggle('is-over', w === over); });
        });

        board.addEventListener('drop', function (event) {
            if (!dragging) { return; }
            var over = event.target instanceof Element ? event.target.closest('[data-fd-widget]') : null;
            if (!over || over === dragging) { return; }
            event.preventDefault();

            var rect = over.getBoundingClientRect();
            var rtl = root.getAttribute('dir') === 'rtl';
            var before = event.clientY < rect.top + rect.height / 2
                || (Math.abs(event.clientY - (rect.top + rect.height / 2)) < rect.height / 4
                    && (rtl ? event.clientX > rect.left + rect.width / 2 : event.clientX < rect.left + rect.width / 2));

            if (before) { board.insertBefore(dragging, over); } else { board.insertBefore(dragging, over.nextElementSibling); }
            dirty = true;
        });

        board.addEventListener('dragend', function () {
            widgets().forEach(function (w) { w.classList.remove('is-over', 'is-dragging'); });
            dragging = null;
        });
    }

    // ------------------------------------------------------------------
    // Filter bar (<x-search-card>): the `search` input moves up into the bar, the other fields
    // live in a panel the Filters button opens, and every active filter shows as a chip that
    // removes itself. The open state is remembered per page.
    // ------------------------------------------------------------------
    function initFilterBars() {
        document.querySelectorAll('[data-fd-filterbar]').forEach(function (bar) {
            var form = bar.querySelector('form');
            var panel = bar.querySelector('[data-fd-filter-panel]');
            var toggle = bar.querySelector('[data-fd-filter-toggle]');
            var chips = bar.querySelector('[data-fd-filter-chips]');
            var count = bar.querySelector('[data-fd-filter-count]');
            var searchSlot = bar.querySelector('[data-fd-filter-search]');
            if (!form || !panel || !toggle) { return; }

            var storageKey = 'fd-filters:' + window.location.pathname;

            function labelOf(control) {
                var text = '';
                if (control.id) {
                    var byFor = form.querySelector('label[for="' + control.id + '"]');
                    if (byFor) { text = byFor.textContent; }
                }
                if (!text) {
                    var wrap = control.closest('label');
                    if (wrap) { text = wrap.textContent; }
                }
                return (text || control.getAttribute('aria-label') || control.getAttribute('placeholder') || control.name || '')
                    .replace(/\*/g, '').replace(/\s+/g, ' ').trim();
            }

            // 1. The search box goes up into the bar.
            var search = panel.querySelector('input[name="search"]');
            if (search && searchSlot) {
                var text = labelOf(search);
                var cell = search.closest('[class*="col-span"]');
                var label = form.querySelector('label[for="' + search.id + '"]');
                search.setAttribute('aria-label', text);
                if (!search.getAttribute('placeholder')) { search.setAttribute('placeholder', text); }
                searchSlot.appendChild(search);
                if (label) { label.remove(); }
                if (cell && !cell.querySelector('input, select, textarea')) { cell.remove(); }
                searchSlot.hidden = false;
            }

            // 2. Chips for the fields that have a value.
            function controls() {
                return Array.prototype.filter.call(panel.querySelectorAll('input, select, textarea'), function (control) {
                    return control.type !== 'hidden' && control.type !== 'submit' && control.type !== 'button'
                        && control.name !== 'per_page' && control.name !== 'search'
                        && !control.classList.contains('select2-search__field');
                });
            }

            function shownValue(control) {
                if (control.tagName === 'SELECT') {
                    return Array.prototype.filter.call(control.selectedOptions, function (option) { return option.value !== ''; })
                        .map(function (option) { return option.textContent.trim(); }).join(', ');
                }
                if (control.type === 'checkbox' || control.type === 'radio') { return control.checked ? labelOf(control) : ''; }
                return control.value.trim();
            }

            function clear(control) {
                if (control.type === 'checkbox' || control.type === 'radio') { control.checked = false; return; }
                if (control.tagName === 'SELECT') {
                    Array.prototype.forEach.call(control.options, function (option) { option.selected = option.value === '' && !control.multiple; });
                    if (window.jQuery) { window.jQuery(control).trigger('change'); }
                    return;
                }
                control.value = '';
            }

            function renderChips() {
                chips.innerHTML = '';
                var seen = {};
                var total = 0;

                controls().forEach(function (control) {
                    var value = shownValue(control);
                    var key = control.name.replace(/\[\]$/, '');
                    if (!value || seen[key]) { return; }
                    seen[key] = true;
                    total += 1;

                    var chip = document.createElement('button');
                    chip.type = 'button';
                    chip.className = 'fd-chip';
                    var name = labelOf(control);
                    chip.setAttribute('aria-label', (name ? name + ': ' : '') + value + ' (remove)');
                    chip.innerHTML = '<span class="fd-chip-name"></span><span class="fd-chip-value"></span><i class="ph-x" aria-hidden="true"></i>';
                    chip.querySelector('.fd-chip-name').textContent = name;
                    chip.querySelector('.fd-chip-value').textContent = value;
                    chip.addEventListener('click', function () {
                        controls().filter(function (other) { return other.name === control.name; }).forEach(clear);
                        form.submit();
                    });
                    chips.appendChild(chip);
                });

                chips.hidden = total === 0;
                count.textContent = String(total);
                count.hidden = total === 0;
            }

            // 3. Opening and closing.
            function setOpen(open) {
                bar.classList.toggle('is-open', open);
                toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
                try { localStorage.setItem(storageKey, open ? 'open' : 'closed'); } catch (e) { /* storage blocked */ }
            }

            var remembered = null;
            try { remembered = localStorage.getItem(storageKey); } catch (e) { /* storage blocked */ }

            bar.classList.add('is-enhanced');
            setOpen(remembered === 'open');
            toggle.addEventListener('click', function () { setOpen(!bar.classList.contains('is-open')); });
            renderChips();
        });
    }

    // ------------------------------------------------------------------
    // Stacked tables on phones: copy each column heading onto its cells as data-label, so the
    // CSS can print it beside the value. Rows that merge cells with colspan are left alone.
    // ------------------------------------------------------------------
    function initTableLabels(scope) {
        (scope || document).querySelectorAll('table.table-stack').forEach(function (table) {
            var head = table.querySelector('thead tr');
            if (!head) { return; }

            var labels = Array.prototype.map.call(head.children, function (th) {
                return th.textContent.replace(/\s+/g, ' ').trim();
            });

            table.querySelectorAll('tbody > tr').forEach(function (row) {
                if (row.querySelector('[colspan]')) { return; }
                Array.prototype.forEach.call(row.children, function (cell, index) {
                    if (cell.tagName === 'TD' && !cell.hasAttribute('data-label')) {
                        cell.setAttribute('data-label', labels[index] || '');
                    }
                    // A cell with nothing in it has nothing to label.
                    if (cell.tagName === 'TD' && !cell.textContent.trim() && !cell.querySelector('img, svg, i, input, button, a')) {
                        cell.setAttribute('data-empty', '');
                    }
                });
            });
        });
    }

    // ------------------------------------------------------------------
    // File drop zones (<x-form.file>): show the chosen file and, for images, a preview.
    // The input itself covers the zone, so picking and dropping are the browser's own.
    // ------------------------------------------------------------------
    function initUploads() {
        function size(bytes) {
            if (bytes >= 1048576) { return (bytes / 1048576).toFixed(1) + ' MB'; }
            return Math.max(1, Math.round(bytes / 1024)) + ' KB';
        }

        document.addEventListener('change', function (event) {
            var input = event.target;
            if (!(input instanceof HTMLInputElement) || input.type !== 'file') { return; }
            var zone = input.closest('[data-fd-upload]');
            if (!zone) { return; }

            var preview = zone.querySelector('.fd-drop-preview');
            var label = zone.querySelector('.fd-drop-file');
            var file = input.files && input.files[0];

            zone.classList.toggle('has-file', !!file);
            if (label) {
                label.hidden = !file;
                label.textContent = file ? file.name + ' · ' + size(file.size) : '';
            }

            if (preview) {
                if (zone._fdUrl) { URL.revokeObjectURL(zone._fdUrl); zone._fdUrl = null; }
                if (file && file.type.indexOf('image/') === 0) {
                    zone._fdUrl = URL.createObjectURL(file);
                    preview.src = zone._fdUrl;
                    preview.hidden = false;
                } else if (!file && zone.dataset.current) {
                    preview.src = zone.dataset.current;
                    preview.hidden = false;
                } else {
                    preview.hidden = true;
                }
            }
        });

        ['dragenter', 'dragover'].forEach(function (name) {
            document.addEventListener(name, function (event) {
                var zone = event.target instanceof Element ? event.target.closest('[data-fd-upload]') : null;
                if (zone) { zone.classList.add('is-dragover'); }
            });
        });
        ['dragleave', 'drop'].forEach(function (name) {
            document.addEventListener(name, function (event) {
                var zone = event.target instanceof Element ? event.target.closest('[data-fd-upload]') : null;
                if (zone) { zone.classList.remove('is-dragover'); }
            });
        });
    }

    // Highlights the section nav entry of the section in view (profile and other long forms).
    function initFormNav() {
        var nav = document.querySelector('.fd-form-nav');
        if (!nav || !('IntersectionObserver' in window)) { return; }

        var links = Array.prototype.slice.call(nav.querySelectorAll('a[href^="#"]'));
        var sections = links.map(function (link) { return document.getElementById(link.getAttribute('href').slice(1)); });

        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) { return; }
                links.forEach(function (link) {
                    link.classList.toggle('is-active', link.getAttribute('href') === '#' + entry.target.id);
                });
            });
        }, { root: document.querySelector('.content-inner'), rootMargin: '-10% 0px -70% 0px' });

        sections.forEach(function (section) { if (section) { observer.observe(section); } });
        if (links[0]) { links[0].classList.add('is-active'); }
    }

    function ready(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback);
        } else {
            callback();
        }
    }

    ready(function () {
        installJqueryShims();
        initSidebar();
        initThemePanel();
        initSelects();
        initFilterBars();
        initTableLabels();
        initUploads();
        initFormNav();
        initDashboard();

        document.addEventListener('fd:modal-shown', function (event) { initSelects(event.target); });
        document.addEventListener('fd:offcanvas-shown', function (event) { initSelects(event.target); });
    });

    window.Foundation = {
        initSelects: initSelects,
        initTableLabels: initTableLabels,
        modal: function (el) { return components.modal(el); },
        offcanvas: function (el) { return components.offcanvas(el); },
        dropdown: function (el) { return components.dropdown(el); },
        collapse: function (el) { return components.collapse(el); },
        tab: function (el) { return components.tab(el); },
        applyColorMode: applyColorMode,
        applyDirection: applyDirection
    };
})();
