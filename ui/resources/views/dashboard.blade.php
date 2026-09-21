<x-app-layout>
    <x-slot name="breadcrumbs">
        <a href="{{ route('admin.dashboard') }}" class="breadcrumb-item">Home</a>
        <span class="breadcrumb-item active">Dashboard</span>
    </x-slot>

    @php
        $greeting = match (true) {
            now()->hour < 12 => 'Good morning',
            now()->hour < 18 => 'Good afternoon',
            default => 'Good evening',
        };
    @endphp

    <x-page-header :title="$greeting.', '.Auth::user()->name" icon="ph-house" :subtitle="now()->format('l, j F Y')" />

    {{-- A project replaces this page by creating resources/views/dashboard.blade.php. --}}

    {{-- Every enabled module contributes its own widget. --}}
    <div class="row g-3">
        @foreach (\Nwidart\Modules\Facades\Module::allEnabled() as $module)
            @php $widgetView = strtolower($module->getName()) . '::partials.dashboard-widget'; @endphp
            @if (view()->exists($widgetView))
                @include($widgetView)
            @endif
        @endforeach
    </div>
</x-app-layout>
