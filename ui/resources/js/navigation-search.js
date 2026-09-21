export function initNavigationSearch() {
    function buildSearchIndex() {
        const links = document.querySelectorAll('#navbar-nav .nav-link, .sidebar .nav-link, #main-sidebar .nav-link, .nav-sidebar .nav-link');
        const searchIndex = [];
        const seenHrefs = new Set();

        links.forEach((link) => {
            const href = link.getAttribute('href');
            if (href && href !== '#' && !href.startsWith('javascript:')) {
                let text = link.textContent.trim();
                text = text.replace(/\s+/g, ' ');

                const iconElement = link.querySelector('i');
                const iconClass = iconElement ? iconElement.className : 'ph-list';

                if (text && !seenHrefs.has(href)) {
                    seenHrefs.add(href);
                    searchIndex.push({
                        text: text,
                        href: href,
                        icon: iconClass,
                    });
                }
            }
        });

        return searchIndex;
    }

    const searchIndex = buildSearchIndex();

    const searchModalHtml = `
        <div id="quickSearchModalOverlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 1050; background-color: rgba(33, 37, 41, 0.5); backdrop-filter: blur(2px); align-items: flex-start; justify-content: center; padding-top: 10vh; font-family: var(--body-font-family, sans-serif);">
            <div style="background-color: var(--bs-body-bg, #ffffff); width: 90%; max-width: 600px; border-radius: 0.5rem; box-shadow: 0 10px 25px rgba(0,0,0,0.2); border: 1px solid var(--bs-border-color, #dee2e6); overflow: hidden; transform: translateY(-20px); transition: transform 0.2s ease-out; display: flex; flex-direction: column; max-height: 80vh;">
                <div style="display: flex; align-items: center; padding: 1rem 1.25rem; border-bottom: 1px solid var(--bs-border-color, #dee2e6);">
                    <i class="ph-magnifying-glass" style="color: var(--bs-secondary-color, #6c757d); margin-right: 12px; font-size: 1.25rem;"></i>
                    <input type="text" id="quickSearchModalInput" placeholder="Search... " style="width: 100%; border: none; outline: none; font-size: 1.1rem; background: transparent; color: var(--bs-body-color, #212529);" autocomplete="off">
                    <span style="font-size: 0.75rem; border: 1px solid var(--bs-border-color, #dee2e6); border-radius: 0.25rem; padding: 0.125rem 0.375rem; color: var(--bs-secondary-color, #6c757d); background-color: var(--bs-tertiary-bg, #f8f9fa); margin-left: 12px; pointer-events: none;">ESC</span>
                </div>
                <div id="quickSearchResultsContainer" style="overflow-y: auto; padding: 0.5rem 0; flex-grow: 1;">
                    <div style="padding: 1rem 1.25rem; color: var(--bs-secondary-text, #6c757d); text-align: center; font-size: 0.9rem;">
                        Type to start searching...
                    </div>
                </div>
            </div>
        </div>
    `;

    document.body.insertAdjacentHTML('beforeend', searchModalHtml);

    const searchOverlay = document.getElementById('quickSearchModalOverlay');
    const searchModalContent = searchOverlay.querySelector('div');
    const searchInput = document.getElementById('quickSearchModalInput');
    const resultsContainer = document.getElementById('quickSearchResultsContainer');

    function openSearchModal() {
        searchOverlay.style.display = 'flex';
        searchInput.value = '';
        renderResults('');

        requestAnimationFrame(() => {
            searchModalContent.style.transform = 'translateY(0)';
            searchInput.focus();
            searchInput.select();
        });
    }

    function closeSearchModal() {
        searchModalContent.style.transform = 'translateY(-20px)';
        setTimeout(() => {
            searchOverlay.style.display = 'none';
        }, 150);
    }

    document.addEventListener('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
            e.preventDefault();
            openSearchModal();
        }

        if (e.key === 'Escape' && searchOverlay.style.display === 'flex') {
            e.preventDefault();
            closeSearchModal();
        }
    });

    const sidebarSearchBtn = document.getElementById('sidebarSearchBtn');
    if (sidebarSearchBtn) {
        sidebarSearchBtn.addEventListener('click', function (e) {
            e.preventDefault();
            openSearchModal();
        });
    }

    searchOverlay.addEventListener('click', function (e) {
        if (e.target === searchOverlay) {
            closeSearchModal();
        }
    });

    function renderResults(query) {
        resultsContainer.innerHTML = '';

        if (query.length === 0) {
            resultsContainer.innerHTML = `
                <div style="padding: 1rem 1.25rem; color: var(--bs-secondary-text, #6c757d); text-align: center; font-size: 0.9rem;">
                    Type to start searching...
                </div>
            `;
            return;
        }

        const results = searchIndex.filter((item) => item.text.toLowerCase().includes(query));

        if (results.length > 0) {
            results.forEach((item, index) => {
                const isActive = index === 0 ? 'background-color: rgba(var(--bs-primary-rgb, 12, 131, 255), 0.1); color: var(--bs-primary, #0c83ff);' : '';
                const aClass = index === 0 ? 'active' : '';

                resultsContainer.innerHTML += `
                    <a href="${item.href}" class="dropdown-item ${aClass}" style="display: flex; align-items: center; padding: 0.625rem 1.25rem; text-decoration: none; color: inherit; transition: background-color 0.15s; ${isActive}">
                        <div style="width: 32px; text-align: center; margin-right: 0.5rem; opacity: 0.7;">
                            <i class="${item.icon}"></i>
                        </div>
                        <span style="font-weight: 500;">${item.text}</span>
                    </a>
                `;
            });

            const resultItems = resultsContainer.querySelectorAll('a.dropdown-item');
            resultItems.forEach((item) => {
                item.addEventListener('mouseenter', () => {
                    resultItems.forEach((ri) => {
                        ri.classList.remove('active');
                        ri.style.backgroundColor = 'transparent';
                        ri.style.color = 'inherit';
                    });
                    item.classList.add('active');
                    item.style.backgroundColor = 'rgba(var(--bs-primary-rgb, 12, 131, 255), 0.1)';
                    item.style.color = 'var(--bs-primary, #0c83ff)';
                });
            });
        } else {
            resultsContainer.innerHTML = `
                <div style="padding: 1rem 1.25rem; color: var(--bs-secondary-text, #6c757d); text-align: center; font-size: 0.9rem;">
                    <i class="ph-warning me-2"></i> No results found
                </div>
            `;
        }
    }

    searchInput.addEventListener('input', function () {
        renderResults(this.value.toLowerCase().trim());
    });

    searchInput.addEventListener('keydown', function (e) {
        if (searchOverlay.style.display !== 'flex') return;

        const items = Array.from(resultsContainer.querySelectorAll('a.dropdown-item'));
        if (items.length === 0) return;

        let currentIndex = items.findIndex((item) => item.classList.contains('active'));

        function setActive(index) {
            items.forEach((item) => {
                item.classList.remove('active');
                item.style.backgroundColor = 'transparent';
                item.style.color = 'inherit';
            });
            if (index >= 0 && index < items.length) {
                items[index].classList.add('active');
                items[index].style.backgroundColor = 'rgba(var(--bs-primary-rgb, 12, 131, 255), 0.1)';
                items[index].style.color = 'var(--bs-primary, #0c83ff)';
                items[index].scrollIntoView({ block: 'nearest' });
            }
        }

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            if (currentIndex < items.length - 1) {
                setActive(currentIndex + 1);
            } else if (currentIndex === -1) {
                setActive(0);
            }
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            if (currentIndex > 0) {
                setActive(currentIndex - 1);
            }
        } else if (e.key === 'Enter') {
            e.preventDefault();
            const activeItem = currentIndex >= 0 ? items[currentIndex] : items[0];
            if (activeItem && activeItem.href) {
                window.location.href = activeItem.href;
            }
        }
    });

    const topNavbarSearchInput = document.querySelector('.navbar-search input[type="text"]');
    if (topNavbarSearchInput && topNavbarSearchInput.placeholder.includes('(Ctrl+K)')) {
        topNavbarSearchInput.placeholder = 'Search';
    }
}
