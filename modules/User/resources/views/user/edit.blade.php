@extends('user::layouts.master')

@section('breadcrumb')
    <a href="{{ route('admin.users.index') }}" class="breadcrumb-item">User List</a>
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
                <x-form.input name="name" label="Full Name" required :value="$user->name" placeholder="Full name" />
            </div>
            <div class="col-md-3">
                <x-form.select class="select" name="gender" label="Gender" required :options="['male' => 'Male', 'female' => 'Female', 'other' => 'Other']" :selected="$user->gender" data-placeholder="Select gender…" />
            </div>
            <div class="col-md-3">
                <x-form.file name="image" id="image-upload" label="Profile Image" accept="image/jpeg,image/png" @change="onImageChange($event)" />
                <div class="form-text">Leave empty to keep current · JPEG or PNG, max 2 MB</div>
            </div>
        </div>
    </x-form-section>

    <x-form-section title="Contact Details" icon="ph-envelope">
        <div class="row g-3">
            <div class="col-md-6">
                <x-form.input type="email" name="email" label="Email" required :value="$user->email" placeholder="email@example.com" />
                @if($user->email_verified_at)
                    <div class="form-text text-success"><i class="ph-check-circle me-1"></i>Verified on {{ $user->email_verified_at->format('d M Y') }}</div>
                @else
                    <div class="form-text text-warning"><i class="ph-warning me-1"></i>Not verified</div>
                @endif
            </div>
            <div class="col-md-6">
                <x-form.input name="phone" label="Mobile No" :value="$user->phone" placeholder="+8801712345678" inputmode="tel" />
                @if($user->phone && $user->phone_verified_at)
                    <div class="form-text text-success"><i class="ph-check-circle me-1"></i>Verified on {{ $user->phone_verified_at->format('d M Y') }}</div>
                @elseif($user->phone)
                    <div class="form-text text-warning"><i class="ph-warning me-1"></i>Not verified</div>
                @else
                    <div class="form-text">With country code, e.g. +8801712345678</div>
                @endif
            </div>
        </div>
    </x-form-section>

    <x-form-section title="Access & Status" icon="ph-shield-check">
        <div class="row g-3">
            <div class="col-md-6">
                <x-form.select class="select" name="roles[]" id="roles" label="Roles" required multiple :options="$roles" :selected="$user->roles->pluck('id')->toArray()" data-placeholder="Select roles…" />
            </div>
            <div class="col-md-6">
                <x-form.select class="select" name="is_active" label="Status" required :options="integerStatus()" :selected="(int) $user->is_active" data-placeholder="Select status…" />
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

</form>
@endsection
