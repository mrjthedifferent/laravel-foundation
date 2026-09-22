@extends('activitylog::layouts.master')

@section('breadcrumb')
<a href="{{ route('admin.email-logs.index') }}" class="breadcrumb-item">{{ __('activitylog::activitylog.email_logs_show.breadcrumb_parent') }}</a>
<span class="breadcrumb-item active">#{{ $emailLog->id }}</span>
@endsection

@php
    $statusBadge = ['sent' => 'success', 'pending' => 'warning', 'failed' => 'danger'];
@endphp

@section('content')
<x-page-header title="{{ __('activitylog::activitylog.email_logs_show.title', ['id' => $emailLog->id]) }}" icon="ph-envelope"
    :back-url="route('admin.email-logs.index')" back-label="{{ __('activitylog::activitylog.email_logs_show.back_label') }}">
    <x-slot name="actions">
        <span class="badge bg-{{ $statusBadge[$emailLog->status] ?? 'secondary' }} fs-sm">
            {{ ucfirst($emailLog->status) }}
        </span>
    </x-slot>
</x-page-header>

<x-form-section title="{{ __('activitylog::activitylog.email_logs_show.details') }}" icon="ph-info">
    <dl class="fd-dl">
        <dt>{{ __('activitylog::activitylog.email_logs_show.to') }}</dt>
        <dd>{{ $emailLog->to_email }}@if ($emailLog->to_name) <span class="text-muted">({{ $emailLog->to_name }})</span>@endif</dd>

        <dt>{{ __('activitylog::activitylog.email_logs_show.from') }}</dt>
        <dd>{{ $emailLog->from_email ?? '—' }}@if ($emailLog->from_name) <span class="text-muted">({{ $emailLog->from_name }})</span>@endif</dd>

        @if ($emailLog->cc)
        <dt>{{ __('activitylog::activitylog.email_logs_show.cc') }}</dt>
        <dd>{{ implode(', ', $emailLog->cc) }}</dd>
        @endif

        @if ($emailLog->bcc)
        <dt>{{ __('activitylog::activitylog.email_logs_show.bcc') }}</dt>
        <dd>{{ implode(', ', $emailLog->bcc) }}</dd>
        @endif

        <dt>{{ __('activitylog::activitylog.email_logs_show.subject') }}</dt>
        <dd>{{ $emailLog->subject ?? '—' }}</dd>

        <dt>{{ __('activitylog::activitylog.email_logs_show.notification') }}</dt>
        <dd>{{ $emailLog->notification ? class_basename($emailLog->notification) : '—' }}</dd>

        <dt>{{ __('activitylog::activitylog.email_logs_show.mailer') }}</dt>
        <dd>{{ $emailLog->mailer ?? '—' }}</dd>

        <dt>{{ __('activitylog::activitylog.email_logs_show.sent_at') }}</dt>
        <dd>{{ $emailLog->sent_at?->format(config('foundation.formats.datetime')) ?? '—' }}</dd>

        <dt>{{ __('foundation::foundation.common.created_at') }}</dt>
        <dd>{{ $emailLog->created_at->format(config('foundation.formats.datetime')) }}</dd>

        @if ($emailLog->error)
        <dt class="text-danger">{{ __('activitylog::activitylog.email_logs_show.error') }}</dt>
        <dd>
            <pre class="bg-danger-subtle text-danger-emphasis p-2 rounded mb-0 overflow-auto">{{ $emailLog->error }}</pre>
        </dd>
        @endif
    </dl>
</x-form-section>

<x-form-section title="{{ __('activitylog::activitylog.email_logs_show.body') }}" icon="ph-file-text">
    @if ($emailLog->body)
    <iframe sandbox srcdoc="{{ $emailLog->body }}" height="480" class="w-100 border rounded" title="{{ __('activitylog::activitylog.email_logs_show.email_body_title') }}"></iframe>
    @else
    <span class="text-muted fst-italic">{{ __('activitylog::activitylog.email_logs_show.no_body_recorded') }}</span>
    @endif
</x-form-section>
@endsection
