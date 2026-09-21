@extends('notification::layouts.push-notification')

@section('breadcrumb')
<a href="{{ route('admin.push.notification.index') }}" class="breadcrumb-item">Push Notifications</a>
<span class="breadcrumb-item active">Send Push Notification</span>
@endsection

@section('content')
{{ Form::open(['route' => 'admin.push.notification.store', 'method' => 'post', 'files' => true]) }}

<x-page-header
    title="Send Push Notification"
    subtitle="Compose and dispatch a push notification"
    icon="ph-paper-plane-tilt"
    :back-url="route('admin.push.notification.index')"
    back-label="Back to List" />

<x-form-section title="Recipient" icon="ph-user">
    <div class="row g-3">
        <div class="col-md-12">
            {!! Form::label('user_id', 'Select User <span class="text-danger">*</span>', ['class' => 'form-label fw-semibold fs-sm'], false) !!}
            {!! Form::select('user_id', $users->mapWithKeys(fn($u) => [$u->id => $u->name.' ('.$u->email.')']), old('user_id'), [
            'class' => 'form-control form-control-sm select',
            'id' => 'user_id',
            'data-placeholder' => 'Search user by name or email…',
            'required',
            ]) !!}
            @error('user_id')<div class="text-danger fs-xs mt-1">{{ $message }}</div>@enderror
        </div>
    </div>
</x-form-section>

<x-form-section title="Content" icon="ph-article">
    <div class="row g-3">
        <div class="col-md-6">
            {!! Form::label('title', 'Title <span class="text-danger">*</span>', ['class' => 'form-label fw-semibold fs-sm'], false) !!}
            {!! Form::text('title', old('title'), ['class' => 'form-control form-control-sm', 'placeholder' => 'Notification title', 'required']) !!}
            @error('title')<div class="text-danger fs-xs mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            {!! Form::label('url', 'URL', ['class' => 'form-label fw-semibold fs-sm']) !!}
            {!! Form::text('url', old('url'), ['class' => 'form-control form-control-sm', 'placeholder' => 'https://…']) !!}
            @error('url')<div class="text-danger fs-xs mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-12">
            {!! Form::label('body', 'Body <span class="text-danger">*</span>', ['class' => 'form-label fw-semibold fs-sm'], false) !!}
            {!! Form::textarea('body', old('body'), ['class' => 'form-control form-control-sm', 'rows' => 3, 'placeholder' => 'Notification message…', 'required']) !!}
            @error('body')<div class="text-danger fs-xs mt-1">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-12">
            {!! Form::label('description', 'Internal Description', ['class' => 'form-label fw-semibold fs-sm']) !!}
            {!! Form::textarea('description', old('description'), ['class' => 'form-control form-control-sm', 'rows' => 2, 'placeholder' => 'Optional internal note…']) !!}
        </div>
        <div class="col-md-12">
            {!! Form::label('image', 'Image', ['class' => 'form-label fw-semibold fs-sm']) !!}
            {!! Form::file('image', ['class' => 'form-control form-control-sm', 'accept' => 'image/*']) !!}
            @error('image')<div class="text-danger fs-xs mt-1">{{ $message }}</div>@enderror
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

{!! Form::close() !!}
@endsection