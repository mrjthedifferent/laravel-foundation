/**
 * The ⌘K palette: one search entry point for the admin.
 *
 * It indexes the sidebar for pages and, when the trigger carries a search URL,
 * queries it for people. Everything is styled through .fd-cmdk* classes.
 */
export function initNavigationSearch() {
    const trigger = document.getElementById('globalSearchTrigger');
    const strings = readStrings(trigger);

    const overlay = document.createElement('div');
    overlay.className = 'fd-cmdk-backdrop';
    overlay.id = 'globalSearchPalette';
    overlay.hidden = true;
    overlay.innerHTML = `
        <div class="fd-cmdk" role="dialog" aria-modal="true" aria-label="${escapeHtml(strings.label)}">
            <div class="fd-cmdk-input">
                <i class="ph-magnifying-glass"></i>
                <input type="text" id="globalSearchPaletteInput" autocomplete="off" placeholder="${escapeHtml(strings.placeholder)}">
                <span class="fd-kbd">Esc</span>
            </div>
            <div class="fd-cmdk-list" id="globalSearchPaletteList"></div>
            <div class="fd-cmdk-foot">
                <span><span class="fd-kbd">↑</span><span class="fd-kbd">↓</span>${escapeHtml(strings.navigate)}</span>
                <span><span class="fd-kbd">↵</span>${escapeHtml(strings.open)}</span>
                <span class="ms-auto">${escapeHtml(strings.dismiss)}</span>
            </div>
        </div>`;
    document.body.appendChild(overlay);

    const input = overlay.querySelector('#globalSearchPaletteInput');
    const list = overlay.querySelector('#globalSearchPaletteList');
    const searchUrl = trigger?.dataset.searchUrl || '';

    let pages = [];
    let people = [];
    let requestTimer = null;
    let requestToken = 0;
    let lastFocused = null;

    /** Pages come from the sidebar, so a project's own menu is searchable for free. */
    function indexPages() {
        const seen = new Set();
        const items = [];

        document.querySelectorAll('#navbar-nav .nav-link').forEach((link) => {
            const href = link.getAttribute('href');
            if (!href || href === '#' || href.startsWith('javascript:')) return;
            if (seen.has(href)) return;
            seen.add(href);

            const label = (link.querySelector('span')?.textContent || link.textContent || '').replace(/\s+/g, ' ').trim();
            if (!label) return;

            const group = link.closest('.nav-group-sub')?.dataset.submenuTitle || '';
            items.push({
                label,
                href,
                hint: group,
                icon: link.querySelector('i')?.className || 'ph-arrow-right',
            });
        });

        return items;
    }

    function open() {
        pages = indexPages();
        people = [];
        lastFocused = document.activeElement;
        input.value = '';
        render('');
        overlay.hidden = false;
        document.body.classList.add('fd-cmdk-open');
        requestAnimationFrame(() => input.focus());
    }

    function close() {
        overlay.hidden = true;
        document.body.classList.remove('fd-cmdk-open');
        clearTimeout(requestTimer);
        requestToken += 1;
        if (lastFocused instanceof HTMLElement) lastFocused.focus();
    }

    function isOpen() {
        return !overlay.hidden;
    }

    function render(query) {
        const matches = query
            ? pages.filter((page) => page.label.toLowerCase().includes(query) || page.hint.toLowerCase().includes(query))
            : pages;

        let html = '';

        if (matches.length) {
            html += `<div class="fd-cmdk-group">${escapeHtml(strings.pages)}</div>`;
            html += matches
                .map(
                    (page) => `
                    <a class="fd-cmdk-item" href="${escapeHtml(page.href)}">
                        <i class="${escapeHtml(page.icon)}"></i>${escapeHtml(page.label)}
                        ${page.hint ? `<span class="fd-cmdk-hint">${escapeHtml(page.hint)}</span>` : ''}
                    </a>`,
                )
                .join('');
        }

        if (people.length) {
            html += `<div class="fd-cmdk-group">${escapeHtml(strings.people)}</div>`;
            html += people
                .map((person) => {
                    const face = person.avatar
                        ? `<img src="${escapeHtml(person.avatar)}" class="fd-avatar fd-avatar-sm" alt="">`
                        : `<i class="${escapeHtml(person.icon || 'ph-user')}"></i>`;
                    return `
                        <a class="fd-cmdk-item" href="${escapeHtml(person.url)}">
                            ${face}${escapeHtml(person.text)}
                            ${person.sub_text ? `<span class="fd-cmdk-hint">${escapeHtml(person.sub_text)}</span>` : ''}
                        </a>`;
                })
                .join('');
        }

        list.innerHTML = html || `<div class="fd-cmdk-empty">${escapeHtml(strings.empty)}</div>`;
        setActive(0);
    }

    function items() {
        return Array.from(list.querySelectorAll('.fd-cmdk-item'));
    }

    function setActive(index) {
        const all = items();
        all.forEach((item) => item.classList.remove('is-active'));
        const item = all[Math.max(0, Math.min(index, all.length - 1))];
        if (item) {
            item.classList.add('is-active');
            item.scrollIntoView({ block: 'nearest' });
        }
    }

    function move(step) {
        const all = items();
        if (!all.length) return;
        const current = all.findIndex((item) => item.classList.contains('is-active'));
        setActive((current + step + all.length) % all.length);
    }

    function searchPeople(query) {
        if (!searchUrl || query.length < 2) {
            people = [];
            render(query);
            return;
        }

        const token = ++requestToken;
        const separator = searchUrl.includes('?') ? '&' : '?';

        fetch(`${searchUrl}${separator}q=${encodeURIComponent(query)}&category=all`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
        })
            .then((response) => (response.ok ? response.json() : Promise.reject(response.status)))
            .then((payload) => {
                if (token !== requestToken) return;
                const rows = Array.isArray(payload?.data) ? payload.data : payload;
                people = Array.isArray(rows) ? rows : [];
                render(query);
            })
            .catch(() => {
                if (token !== requestToken) return;
                people = [];
                render(query);
            });
    }

    input.addEventListener('input', () => {
        const query = input.value.toLowerCase().trim();
        render(query);
        clearTimeout(requestTimer);
        requestTimer = setTimeout(() => searchPeople(query), 250);
    });

    input.addEventListener('keydown', (event) => {
        if (event.key === 'ArrowDown') {
            event.preventDefault();
            move(1);
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            move(-1);
        } else if (event.key === 'Enter') {
            event.preventDefault();
            const item = list.querySelector('.fd-cmdk-item.is-active');
            if (item?.href) window.location.href = item.href;
        }
    });

    overlay.addEventListener('mousedown', (event) => {
        if (event.target === overlay) close();
    });

    document.addEventListener('keydown', (event) => {
        if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
            event.preventDefault();
            isOpen() ? close() : open();
        } else if (event.key === 'Escape' && isOpen()) {
            event.preventDefault();
            close();
        }
    });

    trigger?.addEventListener('click', (event) => {
        event.preventDefault();
        open();
    });

    /* On a Mac the shortcut is ⌘K, so the hint says so. */
    if (/Mac|iPhone|iPad/.test(navigator.platform || navigator.userAgent)) {
        document.querySelectorAll('[data-kbd-mod]').forEach((key) => {
            key.textContent = '⌘';
        });
    }
}

function readStrings(trigger) {
    const defaults = {
        label: 'Search',
        placeholder: 'Search pages and people…',
        pages: 'Pages',
        people: 'People',
        empty: 'No results',
        navigate: 'Navigate',
        open: 'Open',
        dismiss: 'Esc to close',
    };

    try {
        return { ...defaults, ...JSON.parse(trigger?.dataset.searchStrings || '{}') };
    } catch {
        return defaults;
    }
}

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, (character) => {
        switch (character) {
            case '&':
                return '&amp;';
            case '<':
                return '&lt;';
            case '>':
                return '&gt;';
            case '"':
                return '&quot;';
            default:
                return '&#39;';
        }
    });
}
