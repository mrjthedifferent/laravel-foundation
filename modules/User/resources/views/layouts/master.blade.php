<x-app-layout>
    <x-slot name="breadcrumbs">
        <a href="{{ route('admin.dashboard') }}" class="breadcrumb-item">Home</a>
        <a href="{{ route('admin.users.index') }}" class="breadcrumb-item">User</a>
        @yield('breadcrumb')
    </x-slot>

    @yield('content')
</x-app-layout>
