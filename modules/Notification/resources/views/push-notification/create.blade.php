@extends('notification::layouts.push-notification')

@section('breadcrumb')
<a href="{{ route('admin.push.notification.index') }}" class="breadcrumb-item">Push Notifications</a>
<span class="breadcrumb-item active">Send Push Notification</span>
@endsection

@section('content')
<form action="{{ route('admin.push.notification.store') }}" method="POST" enctype="multipart/form-data">
    @csrf

<x-page-header
    title="Send Push Notification"
    subtitle="Compose and dispatch a push notification"
    icon="ph-paper-plane-tilt"
    :back-url="route('admin.push.notification.index')"
    back-label="Back to List" />

<x-form-section title="Recipient" icon="ph-user">
    <div class="row g-3">
        <div class="col-md-12">
            <x-form.select
                class="select"
                name="user_id"
                id="user_id"
                label="Select User"
                required
                :options="$users->mapWithKeys(fn($u) => [$u->id => $u->name.' ('.$u->email.')'])"
                :selected="old('user_id')"
                data-placeholder="Search user by name or email…"
            />
        </div>
    </div>
</x-form-section>

<x-form-section title="Content" icon="ph-article">
    <div class="row g-3">
        <div class="col-md-6">
            <x-form.input name="title" label="Title" required :value="old('title')" placeholder="Notification title" />
        </div>
        <div class="col-md-6">
            <x-form.input name="url" label="URL" :value="old('url')" placeholder="https://…" />
        </div>
        <div class="col-md-12">
            <x-form.textarea name="body" label="Body" required :value="old('body')" :rows="3" placeholder="Notification message…" />
        </div>
        <div class="col-md-12">
            <x-form.textarea name="description" label="Internal Description" :value="old('description')" :rows="2" placeholder="Optional internal note…" />
        </div>
        <div class="col-md-12">
            <x-form.file name="image" label="Image" accept="image/*" />
        </div>
    </div>
</x-form-section>

<div class="d-flex justify-content-between align-items-center">
    <a href="{{ route('admin.push.notification.index') }}" class="btn btn-outline-secondary">
        <i class="ph-x me-1"></i>Cancel
    </a>
    <x-primary-button id="submit-button" class="px-5">
        <i class="ph-paper-plane-tilt me-1"></i>Send Notification
    </x-primary-button>
</div>

</form>
@endsection