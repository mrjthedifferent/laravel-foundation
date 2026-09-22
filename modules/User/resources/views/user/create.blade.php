@extends('user::layouts.master')

@section('breadcrumb')
    <a href="{{ route('admin.users.index') }}" class="breadcrumb-item">User List</a>
    <span class="breadcrumb-item active">Create User</span>
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
    title="Create New User"
    subtitle="Fill in the details below to add a new user"
    icon="ph-user-plus"
    :back-url="route('admin.users.index')"
    back-label="Back to List" />

<x-form-section title="Personal Information" icon="ph-identification-card">
    <div class="row g-3">
        <div class="col-md-6">
            <x-form.input name="name" label="Full Name" required placeholder="Full name" />
        </div>
        <div class="col-md-3">
            <x-form.select class="select" name="gender" label="Gender" required :options="['male' => 'Male', 'female' => 'Female', 'other' => 'Other']" :selected="null" data-placeholder="Select gender…" />
        </div>
        <div class="col-md-3">
            <x-form.file name="image" id="image-upload" label="Profile Image" accept="image/jpeg,image/png" @change="onImageChange($event)" />
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
            <x-form.input type="email" name="email" label="Email" required placeholder="email@example.com" />
        </div>
        <div class="col-md-3">
            <x-form.input name="phone" label="Mobile No" placeholder="+8801712345678" inputmode="tel" />
            <div class="form-text">With country code, e.g. +8801712345678</div>
        </div>
        <div class="col-md-3">
            <x-form.label for="password" required>Password</x-form.label>
            <div class="position-relative">
                <input type="password" name="password" id="password" class="form-control pe-5" placeholder="Min. 8 characters" required x-model="password">
                <button type="button" class="btn border-0 text-muted shadow-none position-absolute top-50 end-0 translate-middle-y" tabindex="-1"
                        @click="togglePassword('password')">
                    <i x-show="!passwordVisible" class="ph-eye"></i>
                    <i x-show="passwordVisible" class="ph-eye-slash" x-cloak></i>
                </button>
            </div>
        </div>
        <div class="col-md-3">
            <x-form.label for="password_confirmation" required>Confirm Password</x-form.label>
            <span class="ms-1">
                <i x-show="passwordConfirmation && password === passwordConfirmation" class="ph-check-circle text-success"></i>
                <i x-show="passwordConfirmation && password !== passwordConfirmation" class="ph-x-circle text-danger"></i>
            </span>
            <div class="position-relative">
                <input type="password" name="password_confirmation" id="password_confirmation" class="form-control pe-5" placeholder="Repeat password" required x-model="passwordConfirmation">
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
            <x-form.select class="select" name="roles[]" id="roles" label="Roles" required multiple :options="$roles" :selected="null" data-placeholder="Select roles…" />
        </div>
        <div class="col-md-6">
            <x-form.select class="select" name="is_active" label="Status" required :options="integerStatus()" selected="1" data-placeholder="Select status…" />
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

</form>
@endsection
