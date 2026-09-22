@extends('notification::layouts.push-notification')

@section('breadcrumb')
<a href="{{ route('admin.push.notification.index') }}" class="breadcrumb-item">{{ __('notification::notification.layouts.push_notifications') }}</a>
<span class="breadcrumb-item active">{{ __('notification::notification.push_notification_create.breadcrumb') }}</span>
@endsection

@section('content')
<form action="{{ route('admin.push.notification.store') }}" method="POST" enctype="multipart/form-data">
    @csrf

<x-page-header
    title="{{ __('notification::notification.push_notification_create.title') }}"
    subtitle="{{ __('notification::notification.push_notification_create.subtitle') }}"
    icon="ph-paper-plane-tilt"
    :back-url="route('admin.push.notification.index')"
    back-label="{{ __('notification::notification.push_notification_create.back_to_list') }}" />

<x-form-section title="{{ __('notification::notification.push_notification_create.recipient_section') }}" icon="ph-user">
    <div class="row g-3">
        <div class="col-md-12">
            <x-form.select
                class="select"
                name="user_id"
                id="user_id"
                label="{{ __('notification::notification.push_notification_create.select_user_label') }}"
                required
                :options="$users->mapWithKeys(fn($u) => [$u->id => $u->name.' ('.$u->email.')'])"
                :selected="old('user_id')"
                data-placeholder="{{ __('notification::notification.push_notification_create.search_user_placeholder') }}"
            />
        </div>
    </div>
</x-form-section>

<x-form-section title="{{ __('notification::notification.push_notification_create.content_section') }}" icon="ph-article">
    <div class="row g-3">
        <div class="col-md-6">
            <x-form.input name="title" label="{{ __('notification::notification.push_notification_create.title_label') }}" required :value="old('title')" placeholder="{{ __('notification::notification.push_notification_create.title_placeholder') }}" />
        </div>
        <div class="col-md-6">
            <x-form.input name="url" label="{{ __('notification::notification.push_notification_create.url_label') }}" :value="old('url')" placeholder="{{ __('notification::notification.push_notification_create.url_placeholder') }}" />
        </div>
        <div class="col-md-12">
            <x-form.textarea name="body" label="{{ __('notification::notification.push_notification_create.body_label') }}" required :value="old('body')" :rows="3" placeholder="{{ __('notification::notification.push_notification_create.body_placeholder') }}" />
        </div>
        <div class="col-md-12">
            <x-form.textarea name="description" label="{{ __('notification::notification.push_notification_create.description_label') }}" :value="old('description')" :rows="2" placeholder="{{ __('notification::notification.push_notification_create.description_placeholder') }}" />
        </div>
        <div class="col-md-12">
            <x-form.file name="image" label="{{ __('notification::notification.push_notification_create.image_label') }}" accept="image/*" />
        </div>
    </div>
</x-form-section>

<div class="d-flex justify-content-between align-items-center">
    <a href="{{ route('admin.push.notification.index') }}" class="btn btn-outline-secondary">
        <i class="ph-x me-1"></i>{{ __('foundation::foundation.common.cancel') }}
    </a>
    <x-primary-button id="submit-button" class="px-5">
        <i class="ph-paper-plane-tilt me-1"></i>{{ __('notification::notification.push_notification_create.send_notification') }}
    </x-primary-button>
</div>

</form>
@endsection