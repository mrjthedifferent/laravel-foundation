<x-app-layout>
    <x-slot name="breadcrumbs">
        <a href="{{ route('admin.dashboard') }}" class="breadcrumb-item d-none d-sm-inline-flex">Home</a>
        <a href="{{ route('admin.push.notification.index') }}" class="breadcrumb-item d-none d-sm-inline-flex">Push Notifications</a>
        @yield('breadcrumb')
    </x-slot>
    @yield('content')
</x-app-layout>