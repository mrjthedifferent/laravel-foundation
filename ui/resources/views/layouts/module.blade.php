<x-app-layout>
    <x-slot name="breadcrumbs">
        <a href="{{ route('admin.dashboard') }}" class="breadcrumb-item d-none d-sm-inline-flex">{{ __('foundation::foundation.layout.home') }}</a>
        <a href="{{ route($route) }}" class="breadcrumb-item d-none d-sm-inline-flex">{{ $label }}</a>
        @yield('breadcrumb')
    </x-slot>

    @yield('content')
</x-app-layout>
