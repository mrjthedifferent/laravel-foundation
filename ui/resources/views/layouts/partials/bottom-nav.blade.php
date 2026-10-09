@php
    $currentRoute = \Illuminate\Support\Facades\Route::currentRouteName();

    // Dashboard, the first three areas the viewer can use, and the menu: five thumb-sized targets.
    $areas = collect(app(\Mrj\Foundation\Support\SidebarMenu::class)->forUser(auth()->user(), $currentRoute))
        ->take(3)
        ->map(fn (array $group): array => [
            'label' => $group['label'],
            'icon' => $group['icon'],
            'href' => $group['items'][0]['href'],
            'active' => $group['open'],
        ]);
@endphp

<nav class="fd-bottomnav" aria-label="{{ __('foundation::foundation.sidebar.navigation') }}">
    <a href="{{ route('admin.dashboard') }}" @class(['is-active' => $currentRoute === 'admin.dashboard'])
        @if ($currentRoute === 'admin.dashboard') aria-current="page" @endif>
        <i class="ph ph-house"></i><span>{{ __('foundation::foundation.layout.dashboard') }}</span>
    </a>
    @foreach ($areas as $area)
        <a href="{{ $area['href'] }}" @class(['is-active' => $area['active']]) @if ($area['active']) aria-current="page" @endif>
            <i class="{{ $area['icon'] }}"></i><span>{{ $area['label'] }}</span>
        </a>
    @endforeach
    <button type="button" class="sidebar-mobile-main-toggle">
        <i class="ph ph-list"></i><span>{{ __('foundation::foundation.sidebar.menu') }}</span>
    </button>
</nav>
