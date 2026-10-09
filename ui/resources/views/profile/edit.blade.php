<x-app-layout>
    <x-slot name="breadcrumbs">
        <a href="{{ route('admin.dashboard') }}" class="breadcrumb-item">{{ __('foundation::foundation.layout.home') }}</a>
        <span class="breadcrumb-item active">{{ __('foundation::foundation.profile.breadcrumb') }}</span>
    </x-slot>

    @php
        $twoFactor = (bool) config('foundation.two_factor.enabled');
        $roles = method_exists($user, 'getRoleNames') ? $user->getRoleNames() : collect();
    @endphp

    <x-page-header :title="__('foundation::foundation.profile.breadcrumb')" :subtitle="$user->email" icon="ph-user-circle" />

    <div class="fd-form-layout">
        <nav class="fd-form-nav" aria-label="{{ __('foundation::foundation.profile.breadcrumb') }}">
            <a href="#profile-information"><i class="ph-identification-card"></i>{{ __('foundation::foundation.profile.information') }}</a>
            <a href="#profile-password"><i class="ph-lock-key"></i>{{ __('foundation::foundation.profile.update_password') }}</a>
            @if ($twoFactor)
                <a href="#profile-two-factor"><i class="ph-shield-check"></i>{{ __('user::user.two_factor.title') }}</a>
            @endif
            <a href="#profile-danger"><i class="ph-warning"></i>{{ __('foundation::foundation.profile.delete_account') }}</a>
        </nav>

        <div class="fd-form-sections">
            <div class="card">
                <div class="fd-identity">
                    @if ($user->image)
                        <img src="{{ $user->image }}" alt="{{ $user->name }}" class="fd-identity-avatar">
                    @else
                        <span class="fd-identity-avatar fd-avatar-fallback"><i class="ph-user"></i></span>
                    @endif
                    <div class="min-w-0">
                        <div class="fd-identity-name">{{ $user->name }}</div>
                        <div class="fd-identity-meta">{{ $user->email }}@if ($user->phone) · {{ $user->phone }}@endif</div>
                        @if ($roles->isNotEmpty())
                            <div class="fd-identity-badges">
                                @foreach ($roles as $role)
                                    <span class="badge badge-primary">{{ $role }}</span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <x-profile-section id="profile-information" icon="ph-identification-card"
                :title="__('foundation::foundation.profile.information')"
                :description="__('foundation::foundation.profile.information_notice')">
                @include('profile.partials.update-profile-information-form')
            </x-profile-section>

            <x-profile-section id="profile-password" icon="ph-lock-key"
                :title="__('foundation::foundation.profile.update_password')"
                :description="__('foundation::foundation.profile.update_password_notice')">
                @include('profile.partials.update-password-form')
            </x-profile-section>

            @if ($twoFactor)
                <x-profile-section id="profile-two-factor" icon="ph-shield-check"
                    :title="__('user::user.two_factor.title')"
                    :description="__('user::user.two_factor.notice')">
                    @include('profile.partials.two-factor-form')
                </x-profile-section>
            @endif

            <x-profile-section id="profile-danger" icon="ph-warning" tone="danger"
                :title="__('foundation::foundation.profile.delete_account')"
                :description="__('foundation::foundation.profile.delete_account_notice')">
                @include('profile.partials.delete-user-form')
            </x-profile-section>
        </div>
    </div>
</x-app-layout>
