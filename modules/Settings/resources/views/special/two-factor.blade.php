@extends('settings::layouts.master')

@section('breadcrumb')
    <span class="breadcrumb-item active">{{ __('settings::settings.special_two_factor.breadcrumb') }}</span>
@endsection

@section('content')
    <form action="{{ route('admin.settings.special.update_two_factor') }}" method="POST">
        @csrf

    <x-page-header title="{{ __('settings::settings.special_two_factor.title') }}"
        subtitle="{{ __('settings::settings.special_two_factor.subtitle') }}"
        icon="ph-shield-check">
        <x-slot name="actions">
            <button type="submit" class="btn btn-primary px-4">
                <i class="ph-floppy-disk"></i>{{ __('settings::settings.special_two_factor.save_changes') }}
            </button>
        </x-slot>
    </x-page-header>

    <x-alert type="primary" icon="ph-info" class="mb-3">
        <span class="fs-sm">{{ __('settings::settings.special_two_factor.info_tip') }}</span>
    </x-alert>

    <x-form-section :title="__('settings::settings.special_two_factor.availability_header')" icon="ph-power">
        <div class="form-check form-switch">
            <input type="hidden" name="enabled" value="0">
            <input class="form-check-input" type="checkbox" role="switch" id="enabled" name="enabled" value="1"
                @checked(old('enabled', $enabled))>
            <label class="form-check-label" for="enabled">{{ __('settings::settings.special_two_factor.enabled_label') }}</label>
        </div>
        <div class="form-text">{{ __('settings::settings.special_two_factor.enabled_help') }}</div>
    </x-form-section>

    <x-form-section :title="__('settings::settings.special_two_factor.required_header')" icon="ph-lock-key">
        <div class="form-check form-switch mb-1">
            <input type="hidden" name="required_for_super_admins" value="0">
            <input class="form-check-input" type="checkbox" role="switch" id="required_for_super_admins"
                name="required_for_super_admins" value="1"
                @checked(old('required_for_super_admins', $requiredForSuperAdmins))>
            <label class="form-check-label" for="required_for_super_admins">{{ __('settings::settings.special_two_factor.super_admins_label') }}</label>
        </div>
        <div class="form-text mb-3">{{ __('settings::settings.special_two_factor.super_admins_help') }}</div>

        <label class="form-label">{{ __('settings::settings.special_two_factor.roles_label') }}</label>
        @if (count($roles))
            <div class="row g-2">
                @foreach ($roles as $role)
                    <div class="col-sm-6 col-lg-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="required_roles[]"
                                id="required_role_{{ $loop->index }}" value="{{ $role }}"
                                @checked(in_array($role, old('required_roles', $requiredRoles), true))>
                            <label class="form-check-label" for="required_role_{{ $loop->index }}">{{ $role }}</label>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-muted fs-sm mb-0">{{ __('settings::settings.special_two_factor.no_roles') }}</p>
        @endif
        @error('required_roles.*')
            <div class="invalid-feedback d-block">{{ $message }}</div>
        @enderror
        <div class="form-text">{{ __('settings::settings.special_two_factor.roles_help') }}</div>
    </x-form-section>

    <x-form-section :title="__('settings::settings.special_two_factor.app_header')" icon="ph-device-mobile">
        <x-form.input name="issuer" id="issuer" :label="__('settings::settings.special_two_factor.issuer_label')"
            :value="$issuer" :placeholder="appName()" :help="__('settings::settings.special_two_factor.issuer_help')" />
    </x-form-section>

    </form>
@endsection
