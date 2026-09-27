@extends('settings::layouts.master')

@section('breadcrumb')
    <span class="breadcrumb-item active">{{ __('settings::settings.special_security.breadcrumb') }}</span>
@endsection

@section('content')
    <form action="{{ route('admin.settings.special.update_security') }}" method="POST">
        @csrf

    <x-page-header title="{{ __('settings::settings.special_security.title') }}"
        subtitle="{{ __('settings::settings.special_security.subtitle') }}"
        icon="ph-shield-check">
        <x-slot name="actions">
            <button type="submit" class="btn btn-primary px-4">
                <i class="ph-floppy-disk"></i>{{ __('settings::settings.special_security.save_changes') }}
            </button>
        </x-slot>
    </x-page-header>

    {{-- Two-factor authentication --}}
    <x-form-section :title="__('settings::settings.special_security.two_factor_header')" icon="ph-device-mobile">
        <p class="text-muted fs-sm">{{ __('settings::settings.special_security.two_factor_info') }}</p>

        @include('settings::special.partials.switch', [
            'name' => 'two_factor_enabled',
            'checked' => $twoFactor['enabled'],
            'label' => __('settings::settings.special_security.two_factor_enabled_label'),
            'help' => __('settings::settings.special_security.two_factor_enabled_help'),
        ])

        @include('settings::special.partials.switch', [
            'name' => 'two_factor_required_for_super_admins',
            'checked' => $twoFactor['required_for_super_admins'],
            'label' => __('settings::settings.special_security.super_admins_label'),
            'help' => __('settings::settings.special_security.super_admins_help'),
        ])

        <label class="form-label">{{ __('settings::settings.special_security.roles_label') }}</label>
        @if (count($roles))
            <div class="row g-2">
                @foreach ($roles as $role)
                    <div class="col-sm-6 col-lg-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="two_factor_required_roles[]"
                                id="required_role_{{ $loop->index }}" value="{{ $role }}"
                                @checked(in_array($role, old('two_factor_required_roles', $twoFactor['required_roles']), true))>
                            <label class="form-check-label" for="required_role_{{ $loop->index }}">{{ $role }}</label>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-muted fs-sm mb-0">{{ __('settings::settings.special_security.no_roles') }}</p>
        @endif
        @error('two_factor_required_roles.*')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
        <div class="form-text mb-3">{{ __('settings::settings.special_security.roles_help') }}</div>

        <x-form.input name="two_factor_issuer" id="two_factor_issuer" :label="__('settings::settings.special_security.issuer_label')"
            :value="$twoFactor['issuer']" :placeholder="appName()" :help="__('settings::settings.special_security.issuer_help')" />
    </x-form-section>

    {{-- Passwords --}}
    <x-form-section :title="__('settings::settings.special_security.passwords_header')" icon="ph-password">
        <div class="row">
            <div class="col-md-6 col-lg-4 mb-3">
                <x-form.input type="number" name="password_min_length" id="password_min_length" min="8" max="128" required
                    :label="__('settings::settings.special_security.min_length_label')" :value="$passwords['min_length']" />
            </div>
        </div>

        @foreach ([
            'mixed_case' => __('settings::settings.special_security.mixed_case_label'),
            'numbers' => __('settings::settings.special_security.numbers_label'),
            'symbols' => __('settings::settings.special_security.symbols_label'),
            'uncompromised' => __('settings::settings.special_security.uncompromised_label'),
        ] as $rule => $label)
            @include('settings::special.partials.switch', [
                'name' => 'password_'.$rule,
                'checked' => $passwords[$rule],
                'label' => $label,
                'help' => $rule === 'uncompromised' ? __('settings::settings.special_security.uncompromised_help') : null,
            ])
        @endforeach
        <div class="form-text">{{ __('settings::settings.special_security.passwords_help') }}</div>
    </x-form-section>

    {{-- Sign-in --}}
    <x-form-section :title="__('settings::settings.special_security.sign_in_header')" icon="ph-sign-in">
        <div class="row g-3">
            <div class="col-md-4">
                <x-form.input type="number" name="login_max_attempts" id="login_max_attempts" min="1" max="100" required
                    :label="__('settings::settings.special_security.max_attempts_label')" :value="$maxAttempts"
                    :help="__('settings::settings.special_security.max_attempts_help')" />
            </div>
            <div class="col-md-4">
                <x-form.input type="number" name="session_lifetime" id="session_lifetime" min="5" max="43200" required
                    :label="__('settings::settings.special_security.session_lifetime_label')" :value="$sessionLifetime"
                    :help="__('settings::settings.special_security.session_lifetime_help')" />
            </div>
            <div class="col-md-4">
                <x-form.input type="number" name="api_token_idle_expiration_minutes" id="api_token_idle_expiration_minutes" min="5" max="525600" required
                    :label="__('settings::settings.special_security.api_token_idle_label')" :value="$apiTokenIdle"
                    :help="__('settings::settings.special_security.api_token_idle_help')" />
            </div>
        </div>
    </x-form-section>

    </form>
@endsection
