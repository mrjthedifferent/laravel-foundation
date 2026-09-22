@extends('user::layouts.master')

@section('breadcrumb')
    <a href="{{ route('admin.users.index') }}" class="breadcrumb-item">{{ __('user::user.index.breadcrumb') }}</a>
    <span class="breadcrumb-item active">{{ __('user::user.create.breadcrumb') }}</span>
@endsection

@section('content')
<form action="{{ route('admin.users.store') }}" method="POST" enctype="multipart/form-data" id="create-user-form">
    @csrf

<div x-data="{
    password: '',
    passwordConfirmation: '',
    imagePreview: null,
    passwordVisible: false,
    passwordConfirmVisible: false,
    togglePassword(id) {
        const input = document.getElementById(id);
        if (input) {
            input.type = input.type === 'password' ? 'text' : 'password';
            if (id === 'password') this.passwordVisible = input.type === 'text';
            else this.passwordConfirmVisible = input.type === 'text';
        }
    },
    onImageChange(e) {
        const file = e.target.files[0];
        if (file && file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = ev => { this.imagePreview = ev.target.result; };
            reader.readAsDataURL(file);
        } else {
            this.imagePreview = null;
        }
    }
}">

<x-page-header
    title="{{ __('user::user.create.title') }}"
    subtitle="{{ __('user::user.create.subtitle') }}"
    icon="ph-user-plus"
    :back-url="route('admin.users.index')"
    back-label="{{ __('user::user.create.back_label') }}" />

<x-form-section title="{{ __('user::user.create.section_personal') }}" icon="ph-identification-card">
    <div class="row g-3">
        <div class="col-md-6">
            <x-form.input name="name" label="{{ __('user::user.create.full_name_label') }}" required placeholder="{{ __('user::user.create.full_name_placeholder') }}" />
        </div>
        <div class="col-md-3">
            <x-form.select class="select" name="gender" label="{{ __('user::user.create.gender_label') }}" required :options="['male' => __('user::user.common.male'), 'female' => __('user::user.common.female'), 'other' => __('user::user.common.other')]" :selected="null" data-placeholder="{{ __('user::user.create.select_gender_placeholder') }}" />
        </div>
        <div class="col-md-3">
            <x-form.file name="image" id="image-upload" label="{{ __('user::user.create.profile_image_label') }}" accept="image/jpeg,image/png" @change="onImageChange($event)" />
            <div class="form-text">{{ __('user::user.create.image_hint') }}</div>
        </div>
        <div class="col-md-3 d-flex align-items-center">
            <img x-show="imagePreview" x-bind:src="imagePreview" alt="Preview"
                class="fd-avatar fd-avatar-lg">
        </div>
    </div>
</x-form-section>

<x-form-section title="{{ __('user::user.create.section_contact') }}" icon="ph-envelope">
    <div class="row g-3">
        <div class="col-md-3">
            <x-form.input type="email" name="email" label="{{ __('user::user.create.email_label') }}" required placeholder="{{ __('user::user.create.email_placeholder') }}" />
        </div>
        <div class="col-md-3">
            <x-form.input name="phone" label="{{ __('user::user.create.phone_label') }}" placeholder="{{ __('user::user.create.phone_placeholder') }}" inputmode="tel" />
            <div class="form-text">{{ __('user::user.create.phone_hint') }}</div>
        </div>
        <div class="col-md-3">
            <x-form.label for="password" required>{{ __('user::user.create.password_label') }}</x-form.label>
            <div class="position-relative">
                <input type="password" name="password" id="password" class="form-control pe-5" placeholder="{{ __('user::user.create.password_placeholder') }}" required x-model="password">
                <button type="button" class="btn btn-ghost btn-icon position-absolute top-50 end-0 translate-middle-y" tabindex="-1"
                        aria-label="{{ __('foundation::foundation.auth.show_password') }}"
                        @click="togglePassword('password')">
                    <i x-show="!passwordVisible" class="ph-eye"></i>
                    <i x-show="passwordVisible" class="ph-eye-slash" x-cloak></i>
                </button>
            </div>
        </div>
        <div class="col-md-3">
            <x-form.label for="password_confirmation" required>{{ __('user::user.create.password_confirmation_label') }}</x-form.label>
            <span class="ms-1">
                <i x-show="passwordConfirmation && password === passwordConfirmation" class="ph-check-circle text-success"></i>
                <i x-show="passwordConfirmation && password !== passwordConfirmation" class="ph-x-circle text-danger"></i>
            </span>
            <div class="position-relative">
                <input type="password" name="password_confirmation" id="password_confirmation" class="form-control pe-5" placeholder="{{ __('user::user.create.password_confirmation_placeholder') }}" required x-model="passwordConfirmation">
                <button type="button" class="btn btn-ghost btn-icon position-absolute top-50 end-0 translate-middle-y" tabindex="-1"
                        aria-label="{{ __('foundation::foundation.auth.show_password') }}"
                        @click="togglePassword('password_confirmation')">
                    <i x-show="!passwordConfirmVisible" class="ph-eye"></i>
                    <i x-show="passwordConfirmVisible" class="ph-eye-slash" x-cloak></i>
                </button>
            </div>
        </div>
    </div>
</x-form-section>

<x-form-section title="{{ __('user::user.create.section_access') }}" icon="ph-shield-check">
    <div class="row g-3">
        <div class="col-md-6">
            <x-form.select class="select" name="roles[]" id="roles" label="{{ __('user::user.create.roles_label') }}" required multiple :options="$roles" :selected="null" data-placeholder="{{ __('user::user.create.select_roles_placeholder') }}" />
        </div>
        <div class="col-md-6">
            <x-form.select class="select" name="is_active" label="{{ __('foundation::foundation.common.status') }}" required :options="integerStatus()" selected="1" data-placeholder="{{ __('user::user.create.select_status_placeholder') }}" />
        </div>
    </div>
</x-form-section>

<div class="d-flex justify-content-between align-items-center">
    <a href="{{ route('admin.users.index') }}" class="btn btn-light">
        <i class="ph-x"></i>{{ __('foundation::foundation.common.cancel') }}
    </a>
    <x-primary-button id="submit-button" class="px-5">
        <i class="ph-user-plus"></i>{{ __('user::user.create.breadcrumb') }}
    </x-primary-button>
</div>
</div>

</form>
@endsection
