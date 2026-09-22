export function initGlobalSearch() {
    const searchInput = document.getElementById('globalSearchInput');
    const searchDropdown = document.getElementById('globalSearchDropdown');
    const searchCancelBtn = document.querySelector('[data-bs-auto-close="outside"]');
    const categoryRadios = document.querySelectorAll('input[name="globalSearchCategory"]');
    const applyBtn = document.getElementById('globalSearchApplyBtn');
    const resetBtn = document.getElementById('globalSearchResetBtn');

    if (!searchInput || !searchDropdown) return;

    let searchTimeout = null;
    let currentCategory = 'all';

    if (applyBtn) {
        applyBtn.addEventListener('click', function () {
            const selectedRadio = Array.from(categoryRadios).find((r) => r.checked);
            if (selectedRadio) {
                currentCategory = selectedRadio.value;
            }

            const bsDropdown = bootstrap.Dropdown.getInstance(searchCancelBtn);
            if (bsDropdown) bsDropdown.hide();

            triggerSearch(searchInput.value);
        });
    }

    if (resetBtn) {
        resetBtn.addEventListener('click', function () {
            const allRadio = Array.from(categoryRadios).find((r) => r.value === 'all');
            if (allRadio) allRadio.checked = true;
            currentCategory = 'all';

            triggerSearch(searchInput.value);
        });
    }

    searchInput.addEventListener('input', function () {
        clearTimeout(searchTimeout);
        const query = this.value.trim();

        if (query.length < 2) {
            searchDropdown.innerHTML = `
                <button type="button" class="dropdown-item">
                    <div class="text-center w-32px me-3">
                        <i class="ph-magnifying-glass"></i>
                    </div>
                    <span>Search everywhere</span>
                </button>
            `;
            return;
        }

        if (!searchDropdown.classList.contains('show')) {
            searchInput.click();
        }

        searchDropdown.innerHTML = `
            <div class="dropdown-item text-muted">
                <div class="text-center w-32px me-3">
                    <i class="ph-spinner spinner"></i>
                </div>
                <span>Searching...</span>
            </div>
        `;

        searchTimeout = setTimeout(() => {
            triggerSearch(query);
        }, 300);
    });

    function triggerSearch(query) {
        if (!query || query.length < 2) return;

        fetch(`/admin/global-search?q=${encodeURIComponent(query)}&category=${encodeURIComponent(currentCategory)}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                Accept: 'application/json',
            },
        })
            .then((response) => {
                if (!response.ok) throw new Error('Network response was not ok');
                return response.json();
            })
            .then((payload) => {
                const rows = Array.isArray(payload?.data) ? payload.data : payload;
                renderResults(rows);
            })
            .catch((error) => {
                console.error('Search error:', error);
                searchDropdown.innerHTML = `
                    <div class="dropdown-item text-danger">
                        <div class="text-center w-32px me-3">
                            <i class="ph-warning-circle"></i>
                        </div>
                        <span>An error occurred while searching.</span>
                    </div>
                `;
            });
    }

    function renderResults(results) {
        searchDropdown.innerHTML = '';

        if (results.length === 0) {
            searchDropdown.innerHTML = `
                <div class="dropdown-item text-muted">
                    <div class="text-center w-32px me-3">
                        <i class="ph-magnifying-glass-minus"></i>
                    </div>
                    <span>No results found</span>
                </div>
            `;
            return;
        }

        results.forEach((item, index) => {
            const avatarHtml = item.avatar
                ? `<img src="${item.avatar}" class="w-32px h-32px rounded-circle me-3" alt="">`
                : `<div class="text-center w-32px me-3"><i class="${item.icon}"></i></div>`;

            const subTextHtml = item.sub_text ? `<div class="text-muted fs-sm">${item.sub_text}</div>` : '';

            const categoryBadge = item.category
                ? `<span class="badge bg-secondary bg-opacity-10 text-secondary ms-auto">${item.category}</span>`
                : '';

            searchDropdown.innerHTML += `
                <a href="${item.url}" class="dropdown-item ${index === 0 ? 'active' : ''} d-flex align-items-center">
                    ${avatarHtml}
                    <div class="flex-column">
                        <span class="fw-semibold">${item.text}</span>
                        ${subTextHtml}
                    </div>
                    ${categoryBadge}
                </a>
            `;
        });

        if (!searchDropdown.classList.contains('show')) {
            searchInput.click();
        }
    }

    searchInput.addEventListener('keydown', function (e) {
        if (!searchDropdown.classList.contains('show')) return;

        const items = Array.from(searchDropdown.querySelectorAll('a.dropdown-item, button.dropdown-item'));
        if (items.length === 0) return;

        let currentIndex = items.findIndex((item) => item.classList.contains('active'));

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            if (currentIndex < items.length - 1) {
                if (currentIndex >= 0) items[currentIndex].classList.remove('active');
                items[currentIndex + 1].classList.add('active');
            } else if (currentIndex === -1) {
                items[0].classList.add('active');
            }
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            if (currentIndex > 0) {
                items[currentIndex].classList.remove('active');
                items[currentIndex - 1].classList.add('active');
            }
        } else if (e.key === 'Enter') {
            e.preventDefault();
            const activeItem = currentIndex >= 0 ? items[currentIndex] : items[0];
            if (activeItem && activeItem.href && activeItem.tagName === 'A') {
                window.location.href = activeItem.href;
            }
        }
    });
}
