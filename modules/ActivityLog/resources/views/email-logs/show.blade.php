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
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label fw-semibold fs-sm text-muted">{{ __('activitylog::activitylog.email_logs_show.to') }}</label>
            <div>{{ $emailLog->to_email }}@if ($emailLog->to_name) <span class="text-muted">({{ $emailLog->to_name }})</span>@endif</div>
        </div>
        <div class="col-md-6">
            <label class="form-label fw-semibold fs-sm text-muted">{{ __('activitylog::activitylog.email_logs_show.from') }}</label>
            <div>{{ $emailLog->from_email ?? '—' }}@if ($emailLog->from_name) <span class="text-muted">({{ $emailLog->from_name }})</span>@endif</div>
        </div>
        @if ($emailLog->cc)
        <div class="col-md-6">
            <label class="form-label fw-semibold fs-sm text-muted">{{ __('activitylog::activitylog.email_logs_show.cc') }}</label>
            <div>{{ implode(', ', $emailLog->cc) }}</div>
        </div>
        @endif
        @if ($emailLog->bcc)
        <div class="col-md-6">
            <label class="form-label fw-semibold fs-sm text-muted">{{ __('activitylog::activitylog.email_logs_show.bcc') }}</label>
            <div>{{ implode(', ', $emailLog->bcc) }}</div>
        </div>
        @endif
        <div class="col-md-12">
            <label class="form-label fw-semibold fs-sm text-muted">{{ __('activitylog::activitylog.email_logs_show.subject') }}</label>
            <div>{{ $emailLog->subject ?? '—' }}</div>
        </div>
        <div class="col-md-4">
            <label class="form-label fw-semibold fs-sm text-muted">{{ __('activitylog::activitylog.email_logs_show.notification') }}</label>
            <div>{{ $emailLog->notification ? class_basename($emailLog->notification) : '—' }}</div>
        </div>
        <div class="col-md-4">
            <label class="form-label fw-semibold fs-sm text-muted">{{ __('activitylog::activitylog.email_logs_show.mailer') }}</label>
            <div>{{ $emailLog->mailer ?? '—' }}</div>
        </div>
        <div class="col-md-4">
            <label class="form-label fw-semibold fs-sm text-muted">{{ __('activitylog::activitylog.email_logs_show.sent_at') }}</label>
            <div>{{ $emailLog->sent_at?->format(config('foundation.formats.datetime')) ?? '—' }}</div>
        </div>
        <div class="col-md-12">
            <label class="form-label fw-semibold fs-sm text-muted">{{ __('foundation::foundation.common.created_at') }}</label>
            <div>{{ $emailLog->created_at->format(config('foundation.formats.datetime')) }}</div>
        </div>
        @if ($emailLog->error)
        <div class="col-md-12">
            <label class="form-label fw-semibold fs-sm text-danger">{{ __('activitylog::activitylog.email_logs_show.error') }}</label>
            <pre class="bg-danger bg-opacity-10 text-danger p-2 rounded mb-0" style="white-space: pre-wrap; word-break: break-all;">{{ $emailLog->error }}</pre>
        </div>
        @endif
    </div>
</x-form-section>

<x-form-section title="{{ __('activitylog::activitylog.email_logs_show.body') }}" icon="ph-file-text">
    @if ($emailLog->body)
    <iframe sandbox srcdoc="{{ $emailLog->body }}" style="width:100%; min-height:480px; border:1px solid var(--bs-border-color); border-radius:.375rem;" title="{{ __('activitylog::activitylog.email_logs_show.email_body_title') }}"></iframe>
    @else
    <span class="text-muted fst-italic">{{ __('activitylog::activitylog.email_logs_show.no_body_recorded') }}</span>
    @endif
</x-form-section>
@endsection
