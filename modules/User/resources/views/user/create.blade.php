@extends('user::layouts.master')

@section('breadcrumb')
    <a href="{{ route('admin.users.index') }}" class="breadcrumb-item">User List</a>
    <span class="breadcrumb-item active">Create User</span>
@endsection

@section('content')
{{ Form::open(['route' => 'admin.users.store', 'method' => 'post', 'files' => true, 'id' => 'create-user-form']) }}

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
    title="Create New User"
    subtitle="Fill in the details below to add a new user"
    icon="ph-user-plus"
    :back-url="route('admin.users.index')"
    back-label="Back to List" />

<x-form-section title="Personal Information" icon="ph-identification-card">
    <div class="row g-3">
        <div class="col-md-6">
            {!! Form::label('name', 'Full Name <span class="text-danger">*</span>', ['class' => 'form-label fw-semibold fs-sm'], false) !!}
            {!! Form::text('name', null, ['class' => 'form-control form-control-sm', 'placeholder' => 'Full name', 'required']) !!}
        </div>
        <div class="col-md-3">
            {!! Form::label('gender', 'Gender <span class="text-danger">*</span>', ['class' => 'form-label fw-semibold fs-sm'], false) !!}
            {!! Form::select('gender', ['male' => 'Male', 'female' => 'Female', 'other' => 'Other'], null, [
            'class' => 'form-control form-control-sm select',
            'data-placeholder' => 'Select gender…',
            'required',
            ]) !!}
        </div>
        <div class="col-md-3">
            {!! Form::label('image', 'Profile Image', ['class' => 'form-label fw-semibold fs-sm']) !!}
            {!! Form::file('image', ['class' => 'form-control form-control-sm', 'accept' => 'image/jpeg,image/png', 'id' => 'image-upload', '@change' => 'onImageChange($event)']) !!}
            <div class="form-text">JPEG or PNG, max 2 MB</div>
        </div>
        <div class="col-md-3 d-flex align-items-center">
            <img x-show="imagePreview" x-bind:src="imagePreview" alt="Preview"
                class="rounded-circle border"
                style="width:52px;height:52px;object-fit:cover;">
        </div>
    </div>
</x-form-section>

<x-form-section title="Contact & Credentials" icon="ph-envelope">
    <div class="row g-3">
        <div class="col-md-3">
            {!! Form::label('email', 'Email <span class="text-danger">*</span>', ['class' => 'form-label fw-semibold fs-sm'], false) !!}
            {!! Form::email('email', null, ['class' => 'form-control form-control-sm', 'placeholder' => 'email@example.com', 'required']) !!}
        </div>
        <div class="col-md-3">
            {!! Form::label('phone', 'Mobile No', ['class' => 'form-label fw-semibold fs-sm']) !!}
            {!! Form::text('phone', null, ['class' => 'form-control form-control-sm', 'placeholder' => '+8801712345678', 'inputmode' => 'tel']) !!}
            <div class="form-text">With country code, e.g. +8801712345678</div>
        </div>
        <div class="col-md-3">
            {!! Form::label('password', 'Password <span class="text-danger">*</span>', ['class' => 'form-label fw-semibold fs-sm'], false) !!}
            <div class="position-relative">
                {!! Form::password('password', ['class' => 'form-control pe-5', 'id' => 'password', 'placeholder' => 'Min. 8 characters', 'required', 'x-model' => 'password']) !!}
                <button type="button" class="btn border-0 text-muted shadow-none position-absolute top-50 end-0 translate-middle-y" tabindex="-1"
                        @click="togglePassword('password')">
                    <i x-show="!passwordVisible" class="ph-eye"></i>
                    <i x-show="passwordVisible" class="ph-eye-slash" x-cloak></i>
                </button>
            </div>
        </div>
        <div class="col-md-3">
            {!! Form::label('password_confirmation', 'Confirm Password <span class="text-danger">*</span>', ['class' => 'form-label fw-semibold fs-sm'], false) !!}
            <span class="ms-1">
                <i x-show="passwordConfirmation && password === passwordConfirmation" class="ph-check-circle text-success"></i>
                <i x-show="passwordConfirmation && password !== passwordConfirmation" class="ph-x-circle text-danger"></i>
            </span>
            <div class="position-relative">
                {!! Form::password('password_confirmation', ['class' => 'form-control pe-5', 'id' => 'password_confirmation', 'placeholder' => 'Repeat password', 'required', 'x-model' => 'passwordConfirmation']) !!}
                <button type="button" class="btn border-0 text-muted shadow-none position-absolute top-50 end-0 translate-middle-y" tabindex="-1"
                        @click="togglePassword('password_confirmation')">
                    <i x-show="!passwordConfirmVisible" class="ph-eye"></i>
                    <i x-show="passwordConfirmVisible" class="ph-eye-slash" x-cloak></i>
                </button>
            </div>
        </div>
    </div>
</x-form-section>

<x-form-section title="Access & Status" icon="ph-shield-check">
    <div class="row g-3">
        <div class="col-md-6">
            {!! Form::label('roles', 'Roles <span class="text-danger">*</span>', ['class' => 'form-label fw-semibold fs-sm'], false) !!}
            {!! Form::select('roles[]', $roles, null, [
            'class' => 'form-control form-control-sm select',
            'multiple',
            'data-placeholder' => 'Select roles…',
            'required',
            'id' => 'roles',
            ]) !!}
        </div>
        <div class="col-md-6">
            {!! Form::label('is_active', 'Status <span class="text-danger">*</span>', ['class' => 'form-label fw-semibold fs-sm'], false) !!}
            {!! Form::select('is_active', integerStatus(), 1, [
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
    <x-primary-button id="submit-button" class="px-5">
        <i class="ph-user-plus me-1"></i>Create User
    </x-primary-button>
</div>
</div>

{{ Form::close() }}
@endsection
