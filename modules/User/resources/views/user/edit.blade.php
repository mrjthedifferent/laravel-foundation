@extends('user::layouts.master')

@section('breadcrumb')
    <a href="{{ route('admin.users.index') }}" class="breadcrumb-item">{{ __('user::user.index.breadcrumb') }}</a>
    <span class="breadcrumb-item active">{{ $user->name }}</span>
@endsection

@section('content')
<form action="{{ route('admin.users.update', $user->id) }}" method="POST" enctype="multipart/form-data" id="edit-user-form">
    @csrf
    @method('PUT')

<div x-data="{ imagePreview: null, onImageChange(e) { const file = e.target.files[0]; if (file && file.type.startsWith('image/')) { const reader = new FileReader(); reader.onload = ev => { this.imagePreview = ev.target.result; }; reader.readAsDataURL(file); } else { this.imagePreview = null; } } }">
    <x-page-header
        title="{{ $user->name }}"
        icon="ph-pencil-simple"
        :back-url="route('admin.users.index')"
        back-label="{{ __('user::user.edit.back_label') }}">
        <x-slot name="actions">
            @foreach($user->roles as $role)
                <span class="badge bg-primary">{{ $role->name }}</span>
            @endforeach
            <x-status-badge :active="$user->is_active" class="fs-xs" />
            <img :src="imagePreview || @js($user->image)" id="img-preview-hero"
                 class="fd-avatar ms-1" alt="{{ $user->name }}">
        </x-slot>
    </x-page-header>

    <x-form-section title="{{ __('user::user.edit.section_personal') }}" icon="ph-identification-card">
        <div class="row g-3">
            <div class="col-md-6">
                <x-form.input name="name" label="{{ __('user::user.edit.full_name_label') }}" required :value="$user->name" placeholder="{{ __('user::user.edit.full_name_placeholder') }}" />
            </div>
            <div class="col-md-3">
                <x-form.select class="select" name="gender" label="{{ __('user::user.edit.gender_label') }}" required :options="['male' => __('user::user.common.male'), 'female' => __('user::user.common.female'), 'other' => __('user::user.common.other')]" :selected="$user->gender" data-placeholder="{{ __('user::user.edit.select_gender_placeholder') }}" />
            </div>
            <div class="col-md-3">
                <x-form.file name="image" id="image-upload" label="{{ __('user::user.edit.profile_image_label') }}" accept="image/jpeg,image/png" @change="onImageChange($event)" />
                <div class="form-text">{{ __('user::user.edit.image_hint') }}</div>
            </div>
        </div>
    </x-form-section>

    <x-form-section title="{{ __('user::user.edit.section_contact') }}" icon="ph-envelope">
        <div class="row g-3">
            <div class="col-md-6">
                <x-form.input type="email" name="email" label="{{ __('user::user.edit.email_label') }}" required :value="$user->email" placeholder="{{ __('user::user.edit.email_placeholder') }}" />
                @if($user->email_verified_at)
                    <div class="form-text text-success"><i class="ph-check-circle me-1"></i>{{ __('user::user.edit.verified_on', ['date' => $user->email_verified_at->format('d M Y')]) }}</div>
                @else
                    <div class="form-text text-warning"><i class="ph-warning me-1"></i>{{ __('user::user.edit.not_verified') }}</div>
                @endif
            </div>
            <div class="col-md-6">
                <x-form.input name="phone" label="{{ __('user::user.edit.phone_label') }}" :value="$user->phone" placeholder="{{ __('user::user.edit.phone_placeholder') }}" inputmode="tel" />
                @if($user->phone && $user->phone_verified_at)
                    <div class="form-text text-success"><i class="ph-check-circle me-1"></i>{{ __('user::user.edit.verified_on', ['date' => $user->phone_verified_at->format('d M Y')]) }}</div>
                @elseif($user->phone)
                    <div class="form-text text-warning"><i class="ph-warning me-1"></i>{{ __('user::user.edit.not_verified') }}</div>
                @else
                    <div class="form-text">{{ __('user::user.edit.phone_hint') }}</div>
                @endif
            </div>
        </div>
    </x-form-section>

    <x-form-section title="{{ __('user::user.edit.section_access') }}" icon="ph-shield-check">
        <div class="row g-3">
            <div class="col-md-6">
                <x-form.select class="select" name="roles[]" id="roles" label="{{ __('user::user.edit.roles_label') }}" required multiple :options="$roles" :selected="$user->roles->pluck('id')->toArray()" data-placeholder="{{ __('user::user.edit.select_roles_placeholder') }}" />
            </div>
            <div class="col-md-6">
                <x-form.select class="select" name="is_active" label="{{ __('foundation::foundation.common.status') }}" required :options="integerStatus()" :selected="(int) $user->is_active" data-placeholder="{{ __('user::user.edit.select_status_placeholder') }}" />
            </div>
        </div>
    </x-form-section>

    <div class="d-flex justify-content-between align-items-center">
        <a href="{{ route('admin.users.index') }}" class="btn btn-light">
            <i class="ph-x"></i>{{ __('foundation::foundation.common.cancel') }}
        </a>
        <x-primary-button id="main-submit-button" class="px-5">
            <i class="ph-floppy-disk"></i>{{ __('user::user.edit.submit') }}
        </x-primary-button>
    </div>
</div>

</form>
@endsection
