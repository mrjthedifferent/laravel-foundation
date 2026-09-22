@extends('errorreport::layouts.master')

@section('breadcrumb')
    <span class="breadcrumb-item active">{{ __('errorreport::errorreport.settings.breadcrumb') }}</span>
@endsection

@section('content')
    <div class="card">
        <div class="card-header d-flex align-items-center gap-2 py-2">
            <div class="bg-primary bg-opacity-10 text-primary rounded d-flex align-items-center justify-content-center flex-shrink-0"
                style="width:32px;height:32px;">
                <i class="ph-gear"></i>
            </div>
            <div>
                <div class="fw-bold">{{ __('errorreport::errorreport.settings.title') }}</div>
                <div class="text-muted fs-xs">{{ __('errorreport::errorreport.settings.subtitle') }}</div>
            </div>
        </div>
        <div class="card-body p-4">
            <form action="{{ route('admin.error-reports.settings.update') }}" method="POST">
                @csrf

                <div class="row g-3">
                    {{-- Enable / Disable --}}
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body py-3">
                                <div class="form-check form-switch">
                                    <input type="hidden" name="error_report_enabled" value="0">
                                    <input type="checkbox" class="form-check-input" name="error_report_enabled"
                                        id="error_report_enabled" value="1"
                                        {{ optional($settings['error_report_enabled'] ?? null)->value ?? false ? 'checked' : '' }}>
                                    <label class="form-check-label fw-semibold" for="error_report_enabled">{{ __('errorreport::errorreport.settings.enable_label') }}</label>
                                </div>
                                <div class="form-text">{{ __('errorreport::errorreport.settings.enable_help') }}</div>
                            </div>
                        </div>
                    </div>

                    {{-- Channels --}}
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header py-2 bg-body-tertiary">
                                <span class="fw-bold fs-sm">{{ __('errorreport::errorreport.settings.channels_title') }}</span>
                            </div>
                            <div class="card-body">
                                @php
                                    $raw = optional($settings['error_report_channels'] ?? null)->value ?? [];
                                    $decoded = is_string($raw) ? json_decode($raw, true) : null;
                                    $channels = is_array($decoded)
                                        ? $decoded
                                        : (is_array($raw)
                                            ? $raw
                                            : (is_string($raw)
                                                ? [$raw]
                                                : []));
                                @endphp
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" name="error_report_channels[]"
                                        id="ch_mail" value="mail" {{ in_array('mail', $channels) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="ch_mail">{{ __('errorreport::errorreport.settings.channel_email') }}</label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" name="error_report_channels[]"
                                        id="ch_slack" value="slack" {{ in_array('slack', $channels) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="ch_slack">{{ __('errorreport::errorreport.settings.channel_slack') }}</label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" name="error_report_channels[]"
                                        id="ch_telegram" value="telegram"
                                        {{ in_array('telegram', $channels) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="ch_telegram">{{ __('errorreport::errorreport.settings.channel_telegram') }}</label>
                                </div>
                                <div class="form-text mt-1">{{ __('errorreport::errorreport.settings.channels_help') }}</div>
                            </div>
                        </div>
                    </div>

                    {{-- Throttle --}}
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header py-2 bg-body-tertiary">
                                <span class="fw-bold fs-sm">{{ __('errorreport::errorreport.settings.throttle_title') }}</span>
                            </div>
                            <div class="card-body">
                                <x-form.input
                                    type="number"
                                    name="error_report_throttle_minutes"
                                    label="{{ __('errorreport::errorreport.settings.throttle_label') }}"
                                    :value="optional($settings['error_report_throttle_minutes'] ?? null)->value ?? 60"
                                    min="1"
                                    max="10080"
                                />
                                <div class="form-text">{{ __('errorreport::errorreport.settings.throttle_help') }}</div>
                            </div>
                        </div>
                    </div>

                    {{-- Email recipients --}}
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header py-2 bg-body-tertiary">
                                <span class="fw-bold fs-sm">{{ __('errorreport::errorreport.settings.email_title') }}</span>
                            </div>
                            <div class="card-body">
                                <x-form.input
                                    name="error_report_email_recipients"
                                    label="{{ __('errorreport::errorreport.settings.email_recipients_label') }}"
                                    :value="optional($settings['error_report_email_recipients'] ?? null)->value ?? ''"
                                    placeholder="admin@example.com, dev@example.com"
                                />
                                <div class="form-text">{{ __('errorreport::errorreport.settings.email_help') }}</div>
                            </div>
                        </div>
                    </div>

                    {{-- Slack --}}
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div
                                class="card-header py-2 bg-body-tertiary d-flex align-items-center justify-content-between">
                                <span class="fw-bold fs-sm">{{ __('errorreport::errorreport.settings.slack_title') }}</span>
                                <button type="button" class="btn btn-link btn-sm p-0 text-muted" data-bs-toggle="collapse"
                                    data-bs-target="#slack-help" aria-expanded="false">
                                    <i class="ph-question"></i> {{ __('errorreport::errorreport.settings.how_to_get') }}
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="collapse" id="slack-help">
                                    <x-alert type="info">
                                        <span class="fs-sm">{!! __('errorreport::errorreport.settings.slack_help') !!}</span>
                                    </x-alert>
                                </div>
                                <x-form.input
                                    type="url"
                                    name="error_report_slack_webhook"
                                    label="{{ __('errorreport::errorreport.settings.slack_webhook_label') }}"
                                    :value="optional($settings['error_report_slack_webhook'] ?? null)->value ?? ''"
                                    placeholder="https://hooks.slack.com/..."
                                />
                            </div>
                        </div>
                    </div>

                    {{-- Telegram --}}
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div
                                class="card-header py-2 bg-body-tertiary d-flex align-items-center justify-content-between">
                                <span class="fw-bold fs-sm">{{ __('errorreport::errorreport.settings.telegram_title') }}</span>
                                <button type="button" class="btn btn-link btn-sm p-0 text-muted" data-bs-toggle="collapse"
                                    data-bs-target="#telegram-help" aria-expanded="false">
                                    <i class="ph-question"></i> {{ __('errorreport::errorreport.settings.how_to_get') }}
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="collapse" id="telegram-help">
                                    <x-alert type="info">
                                        <span class="fs-sm">{!! __('errorreport::errorreport.settings.telegram_help') !!}</span>
                                    </x-alert>
                                </div>
                                <x-form.input
                                    name="error_report_telegram_bot_token"
                                    label="{{ __('errorreport::errorreport.settings.telegram_bot_token_label') }}"
                                    :value="optional($settings['error_report_telegram_bot_token'] ?? null)->value ?? ''"
                                    placeholder="{{ __('errorreport::errorreport.settings.telegram_bot_token_placeholder') }}"
                                />
                                <x-form.label for="error_report_telegram_chat_id" class="mt-2">{{ __('errorreport::errorreport.settings.telegram_chat_id_label') }}</x-form.label>
                                <x-form.input
                                    name="error_report_telegram_chat_id"
                                    :value="optional($settings['error_report_telegram_chat_id'] ?? null)->value ?? ''"
                                    placeholder="{{ __('errorreport::errorreport.settings.telegram_chat_id_placeholder') }}"
                                />
                            </div>
                        </div>
                    </div>

                    {{-- Don't report --}}
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header py-2 bg-body-tertiary">
                                <span class="fw-bold fs-sm">{{ __('errorreport::errorreport.settings.ignore_title') }}</span>
                            </div>
                            <div class="card-body">
                                @php $dontReport = optional($settings['error_report_dont_report'] ?? null)->value ?? []; @endphp
                                @php $dontReportStr = is_array($dontReport) ? implode("\n", $dontReport) : (is_string($dontReport) ? $dontReport : ''); @endphp
                                <x-form.textarea
                                    name="error_report_dont_report"
                                    label="{{ __('errorreport::errorreport.settings.ignore_label') }}"
                                    :value="$dontReportStr"
                                    class="font-monospace"
                                    :rows="5"
                                    placeholder="Illuminate\Auth\AuthenticationException&#10;Symfony\Component\HttpKernel\Exception\NotFoundHttpException"
                                />
                                <div class="form-text">{{ __('errorreport::errorreport.settings.ignore_help') }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end mt-4">
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="ph-floppy-disk me-1"></i> {{ __('errorreport::errorreport.settings.save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
