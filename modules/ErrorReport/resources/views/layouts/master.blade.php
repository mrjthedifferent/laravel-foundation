<x-app-layout>
    <x-slot name="breadcrumbs">
        <a href="{{ route('admin.dashboard') }}" class="breadcrumb-item d-none d-sm-inline-flex">Home</a>
        <a href="{{ route('admin.error-reports.index') }}" class="breadcrumb-item d-none d-sm-inline-flex">Error Reports</a>
        @yield('breadcrumb')
    </x-slot>
    @yield('content')
</x-app-layout>