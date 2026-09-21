<x-app-layout>
    <x-slot name="breadcrumbs">
        <a href="{{ route('admin.dashboard') }}" class="breadcrumb-item">Home</a>
        <span class="breadcrumb-item active">Profile</span>
    </x-slot>

    <div class="py-12">
        <div class="card card-body p-4">
            <div class="max-w-xl">
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>

        <div class="card card-body p-4">
            <div class="max-w-xl">
                @include('profile.partials.update-password-form')
            </div>
        </div>

        <div class="card card-body p-4">
            <div class="max-w-xl">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
</x-app-layout>
