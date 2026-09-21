@extends('user::layouts.master')

@section('breadcrumb')
    <a href="{{ route('admin.users.index') }}" class="breadcrumb-item">User List</a>
    <span class="breadcrumb-item active">{{ $user->name }}</span>
@endsection

@section('content')
{{ Form::model($user, ['route' => ['admin.users.update', $user->id], 'method' => 'put', 'files' => true, 'id' => 'edit-user-form']) }}

<div x-data="{ imagePreview: null, onImageChange(e) { const file = e.target.files[0]; if (file && file.type.startsWith('image/')) { const reader = new FileReader(); reader.onload = ev => { this.imagePreview = ev.target.result; }; reader.readAsDataURL(file); } else { this.imagePreview = null; } } }">
    <x-page-header
        title="{{ $user->name }}"
        icon="ph-pencil-simple"
        :back-url="route('admin.users.index')"
        back-label="Back to List">
        <x-slot name="actions">
            @foreach($user->roles as $role)
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle fs-xs">{{ $role->name }}</span>
            @endforeach
            <x-status-badge :active="$user->is_active" class="fs-xs" />
            <img :src="imagePreview || @js($user->image)" id="img-preview-hero"
                 class="rounded-circle border ms-1"
                 style="width:32px;height:32px;object-fit:cover;" alt="{{ $user->name }}">
        </x-slot>
    </x-page-header>

    <x-form-section title="Personal Information" icon="ph-identification-card">
        <div class="row g-3">
            <div class="col-md-6">
                {!! Form::label('name', 'Full Name <span class="text-danger">*</span>', ['class' => 'form-label fw-semibold fs-sm'], false) !!}
                {!! Form::text('name', $user->name, ['class' => 'form-control form-control-sm', 'placeholder' => 'Full name', 'required']) !!}
            </div>
            <div class="col-md-3">
                {!! Form::label('gender', 'Gender <span class="text-danger">*</span>', ['class' => 'form-label fw-semibold fs-sm'], false) !!}
                {!! Form::select('gender', ['male' => 'Male', 'female' => 'Female', 'other' => 'Other'], enum_value($user->gender), [
                    'class' => 'form-control form-control-sm select',
                    'data-placeholder' => 'Select gender…',
                    'required',
                ]) !!}
            </div>
            <div class="col-md-3">
                {!! Form::label('image', 'Profile Image', ['class' => 'form-label fw-semibold fs-sm']) !!}
                {!! Form::file('image', ['class' => 'form-control form-control-sm', 'accept' => 'image/jpeg,image/png', 'id' => 'image-upload', '@change' => 'onImageChange($event)']) !!}
                <div class="form-text">Leave empty to keep current · JPEG or PNG, max 2 MB</div>
            </div>
        </div>
    </x-form-section>

    <x-form-section title="Contact Details" icon="ph-envelope">
        <div class="row g-3">
            <div class="col-md-6">
                {!! Form::label('email', 'Email <span class="text-danger">*</span>', ['class' => 'form-label fw-semibold fs-sm'], false) !!}
                {!! Form::email('email', $user->email, ['class' => 'form-control form-control-sm', 'placeholder' => 'email@example.com', 'required']) !!}
                @if($user->email_verified_at)
                    <div class="form-text text-success"><i class="ph-check-circle me-1"></i>Verified on {{ $user->email_verified_at->format('d M Y') }}</div>
                @else
                    <div class="form-text text-warning"><i class="ph-warning me-1"></i>Not verified</div>
                @endif
            </div>
        </div>
    </x-form-section>

    <x-form-section title="Access & Status" icon="ph-shield-check">
        <div class="row g-3">
            <div class="col-md-6">
                {!! Form::label('roles', 'Roles <span class="text-danger">*</span>', ['class' => 'form-label fw-semibold fs-sm'], false) !!}
                {!! Form::select('roles[]', $roles, $user->roles->pluck('id')->toArray(), [
                    'class' => 'form-control form-control-sm select',
                    'multiple',
                    'data-placeholder' => 'Select roles…',
                    'required',
                    'id' => 'roles',
                ]) !!}
            </div>
            <div class="col-md-6">
                {!! Form::label('is_active', 'Status <span class="text-danger">*</span>', ['class' => 'form-label fw-semibold fs-sm'], false) !!}
                {!! Form::select('is_active', integerStatus(), (int) $user->is_active, [
                    'class' => 'form-control form-control-sm select',
                    'data-placeholder' => 'Select status…',
                    'required',
                ]) !!}
            </div>
        </div>
    </x-form-section>

    <div class="d-flex justify-content-between align-items-center">
        <a href="{{ route('admin.users.index') }}" class="btn btn-outline-secondary">
            <i class="ph-x me-1"></i>Cancel
        </a>
        <x-primary-button id="main-submit-button" class="px-5">
            <i class="ph-floppy-disk me-1"></i>Update User
        </x-primary-button>
    </div>
</div>

{{ Form::close() }}
@endsection
