{{-- The breadcrumb sits on the page, above the title a page renders with <x-page-header>. --}}
<nav class="fd-breadcrumb-row" aria-label="{{ __('foundation::foundation.layout.breadcrumb') }}">
    <div class="breadcrumb">
        <a href="{{ route('admin.dashboard') }}" class="breadcrumb-item">
            <i class="ph-house"></i>
            <span class="sr-only">{{ __('foundation::foundation.layout.home') }}</span>
        </a>
        {{ $breadcrumbs ?? '' }}
    </div>
</nav>
