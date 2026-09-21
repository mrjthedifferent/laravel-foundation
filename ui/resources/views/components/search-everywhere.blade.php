@props([
    'searchUrl' => null,
    'usersIndexUrl' => null,
    'userShowBaseUrl' => null,
])
@php
    $searchUrl = $searchUrl ?? route('admin.search');
    $usersIndexUrl = $usersIndexUrl ?? route('admin.users.index');
    $userShowBaseUrl = $userShowBaseUrl ?? str_replace('__ID__', '', route('admin.users.show', ['user' => '__ID__']));
@endphp
<div class="form-control-feedback form-control-feedback-start flex-grow-1" data-color-theme="dark"
     x-data="{
         query: '',
         loading: false,
         results: null,
         informMessage: null,
         searchUrl: @js($searchUrl),
         usersIndexUrl: @js($usersIndexUrl),
         userShowBaseUrl: @js($userShowBaseUrl),
         userUrl(id) { return this.userShowBaseUrl + id; },
         async search() {
             if (this.query.length < 3) {
                 this.results = null;
                 this.informMessage = this.query.length > 0 ? 'min-chars' : null;
                 return;
             }
             this.loading = true;
             this.informMessage = null;
             try {
                 const resp = await fetch(`${this.searchUrl}?search=${encodeURIComponent(this.query)}`);
                 const payload = await resp.json();
                 this.results = payload.data !== undefined ? payload.data : payload;
                 this.informMessage = null;
             } catch (e) {
                 this.results = null;
                 this.informMessage = 'error';
             } finally {
                 this.loading = false;
             }
         }
     }"
     x-init="$watch('query', () => search())">
    <input type="text"
           class="form-control bg-transparent rounded-pill border-white border-opacity-25"
           placeholder="Search"
           data-bs-toggle="dropdown"
           x-model.debounce.300ms="query">
    <div class="form-control-feedback-icon">
        <i class="ph-magnifying-glass"></i>
    </div>
    <div class="dropdown-menu w-100" data-color-theme="light">
        <button type="button" class="dropdown-item">
            <div class="text-center w-32px me-3">
                <i class="ph-magnifying-glass"></i>
            </div>
            <span>Search <span class="fw-bold">"in"</span> everywhere</span>
        </button>

        <div x-show="loading" x-transition class="dropdown-item text-muted">
            <div class="text-center w-32px me-3 d-inline-block"><i class="ph-spinner spinner"></i></div>
            <span>Searching...</span>
        </div>
        <div x-show="informMessage === 'min-chars'" x-transition>
            <button type="button" class="dropdown-item">
                <div class="text-center w-100">
                    <i class="ph-x-circle fs-3 text-danger"></i>
                    <p class="text-muted text-center mb-0">Please write at least 3 characters...</p>
                </div>
            </button>
        </div>

        <div class="dropdown-divider" x-show="results"></div>
        <div class="dropdown-menu-scrollable-lg" x-show="results">
            <template x-if="results && results.users && results.users.length">
                <div>
                    <div class="dropdown-header">
                        Users
                        <a :href="`${usersIndexUrl}?search=${encodeURIComponent(query)}`" class="float-end">
                            See all
                            <i class="ph-arrow-circle-right ms-1"></i>
                        </a>
                    </div>
                    <template x-for="user in (results?.users || [])" :key="user.id">
                        <a :href="userUrl(user.id)" class="dropdown-item cursor-pointer d-flex align-items-center">
                            <div class="me-3">
                                <img :src="user.image" class="w-32px h-32px rounded-circle" alt="">
                            </div>
                            <div class="d-flex flex-column flex-grow-1 text-truncate">
                                <div class="fw-semibold" x-text="user.name || ''"></div>
                                <span class="fs-sm text-muted text-truncate" x-text="user.phone || ''"></span>
                            </div>
                            <div class="d-inline-flex">
                                <i class="ph-user-circle text-body ms-2"></i>
                            </div>
                        </a>
                    </template>
                </div>
            </template>
        </div>
    </div>
</div>
