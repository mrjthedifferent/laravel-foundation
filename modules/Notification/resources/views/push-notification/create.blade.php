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
    icon="ph ph-paper-plane-tilt"
    :back-url="route('admin.push.notification.index')"
    back-label="{{ __('notification::notification.push_notification_create.back_to_list') }}" />

<x-form-section title="{{ __('notification::notification.push_notification_create.recipient_section') }}" icon="ph ph-user">
    <div class="grid grid-cols-12 gap-4">
        <div class="col-span-12 md:col-span-4">
            <x-form.select
                name="recipient_type"
                id="recipient_type"
                label="{{ __('notification::notification.push_notification_create.recipient_type_label') }}"
                required
                :options="[
                    'specific' => __('notification::notification.push_notification_create.recipient_specific'),
                    'all' => __('notification::notification.push_notification_create.recipient_all'),
                    'role' => __('notification::notification.push_notification_create.recipient_role'),
                ]"
                :selected="old('recipient_type', 'specific')"
            />
        </div>
        <div class="col-span-12 md:col-span-8" data-recipient="specific">
            <x-form.select
                class="select"
                name="user_id"
                id="user_id"
                label="{{ __('notification::notification.push_notification_create.select_user_label') }}"
                :options="$users->mapWithKeys(fn($u) => [$u->id => $u->name.' ('.($u->email ?? $u->phone).')'])"
                :selected="old('user_id')"
                data-placeholder="{{ __('notification::notification.push_notification_create.search_user_placeholder') }}"
            />
        </div>
        <div class="col-span-12 md:col-span-8" data-recipient="role">
            <x-form.select
                name="recipient_role"
                id="recipient_role"
                label="{{ __('notification::notification.push_notification_create.role_label') }}"
                :options="array_combine($roles, $roles) ?: []"
                :selected="old('recipient_role')"
            />
        </div>
    </div>
</x-form-section>

<x-form-section title="{{ __('notification::notification.push_notification_create.content_section') }}" icon="ph ph-article">
    <div class="grid grid-cols-12 gap-4">
        <div class="col-span-12 md:col-span-6">
            <x-form.input name="title" label="{{ __('notification::notification.push_notification_create.title_label') }}" required :value="old('title')" placeholder="{{ __('notification::notification.push_notification_create.title_placeholder') }}" />
        </div>
        <div class="col-span-12 md:col-span-6">
            <x-form.input name="url" label="{{ __('notification::notification.push_notification_create.url_label') }}" :value="old('url')" placeholder="{{ __('notification::notification.push_notification_create.url_placeholder') }}" :help="__('notification::notification.push_notification_create.url_help')" />
        </div>
        <div class="col-span-12 md:col-span-12">
            <x-form.textarea name="body" label="{{ __('notification::notification.push_notification_create.body_label') }}" required :value="old('body')" :rows="3" placeholder="{{ __('notification::notification.push_notification_create.body_placeholder') }}" />
        </div>
        <div class="col-span-12 md:col-span-12">
            <x-form.textarea name="description" label="{{ __('notification::notification.push_notification_create.description_label') }}" :value="old('description')" :rows="2" placeholder="{{ __('notification::notification.push_notification_create.description_placeholder') }}" />
        </div>
        <div class="col-span-12 md:col-span-12">
            <x-form.file name="image" label="{{ __('notification::notification.push_notification_create.image_label') }}" accept="image/*" :help="__('notification::notification.push_notification_create.image_help')" />
        </div>
    </div>
</x-form-section>

<div class="fd-form-actions">
    <a href="{{ route('admin.push.notification.index') }}" class="btn btn-light">
        <i class="ph ph-x"></i>{{ __('foundation::foundation.common.cancel') }}
    </a>
    <x-primary-button id="submit-button" class="px-12">
        <i class="ph ph-paper-plane-tilt"></i>{{ __('notification::notification.push_notification_create.send_notification') }}
    </x-primary-button>
</div>

</form>
@endsection

@push('scripts')
<script>
$(function () {
    // Show only the field the chosen audience needs.
    var $type = $('#recipient_type');
    function sync() {
        $('[data-recipient]').each(function () {
            $(this).toggleClass('hidden', $(this).data('recipient') !== $type.val());
        });
    }
    $type.on('change', sync);
    sync();
});
</script>
@endpush
