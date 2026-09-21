<x-app-layout>
    <x-slot name="breadcrumbs">
        <a href="{{ route('admin.dashboard') }}" class="breadcrumb-item d-none d-sm-inline-flex">Home</a>
        <a href="{{ route('admin.role.index') }}" class="breadcrumb-item d-none d-sm-inline-flex">Roles & Permissions</a>
        @yield('breadcrumb')
    </x-slot>

    @yield('content')
</x-app-layout>
