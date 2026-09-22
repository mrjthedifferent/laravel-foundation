@extends('errorreport::layouts.master')

@section('breadcrumb')
    <span class="breadcrumb-item active">{{ __('errorreport::errorreport.settings.breadcrumb') }}</span>
@endsection

@section('content')
    <div class="card">
        <div class="card-header">
            <span class="fd-icon-tile fd-icon-tile-sm"><i class="ph-gear"></i></span>
            <div>
                <div class="card-title">{{ __('errorreport::errorreport.settings.title') }}</div>
                <div class="text-muted fs-xs">{{ __('errorreport::errorreport.settings.subtitle') }}</div>
            </div>
        </div>
        <div class="card-body p-4">
            <form action="{{ route('admin.error-reports.settings.update') }}" method="POST">
                @csrf

                {{-- Enable / Disable --}}
                <div class="fd-form-section">
                    <div>
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

                {{-- Channels --}}
                <div class="fd-form-section">
                    <div>
                        <h2 class="fd-form-section-title">{{ __('errorreport::errorreport.settings.channels_title') }}</h2>
                        <p class="fd-form-section-text">{{ __('errorreport::errorreport.settings.channels_help') }}</p>
                    </div>
                    <div>
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
                    </div>
                </div>

                {{-- Throttle --}}
                <div class="fd-form-section">
                    <div>
                        <h2 class="fd-form-section-title">{{ __('errorreport::errorreport.settings.throttle_title') }}</h2>
                        <p class="fd-form-section-text">{{ __('errorreport::errorreport.settings.throttle_help') }}</p>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <x-form.input
                                type="number"
                                name="error_report_throttle_minutes"
                                label="{{ __('errorreport::errorreport.settings.throttle_label') }}"
                                :value="optional($settings['error_report_throttle_minutes'] ?? null)->value ?? 60"
                                min="1"
                                max="10080"
                            />
                        </div>
                    </div>
                </div>

                {{-- Email recipients --}}
                <div class="fd-form-section">
                    <div>
                        <h2 class="fd-form-section-title">{{ __('errorreport::errorreport.settings.email_title') }}</h2>
                        <p class="fd-form-section-text">{{ __('errorreport::errorreport.settings.email_help') }}</p>
                    </div>
                    <div>
                        <x-form.input
                            name="error_report_email_recipients"
                            label="{{ __('errorreport::errorreport.settings.email_recipients_label') }}"
                            :value="optional($settings['error_report_email_recipients'] ?? null)->value ?? ''"
                            placeholder="admin@example.com, dev@example.com"
                        />
                    </div>
                </div>

                {{-- Slack --}}
                <div class="fd-form-section">
                    <div class="d-flex flex-wrap align-items-start gap-2">
                        <div>
                            <h2 class="fd-form-section-title">{{ __('errorreport::errorreport.settings.slack_title') }}</h2>
                        </div>
                        <button type="button" class="btn btn-ghost btn-sm ms-auto" data-bs-toggle="collapse"
                            data-bs-target="#slack-help" aria-expanded="false">
                            <i class="ph-question"></i>{{ __('errorreport::errorreport.settings.how_to_get') }}
                        </button>
                    </div>
                    <div>
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

                {{-- Telegram --}}
                <div class="fd-form-section">
                    <div class="d-flex flex-wrap align-items-start gap-2">
                        <div>
                            <h2 class="fd-form-section-title">{{ __('errorreport::errorreport.settings.telegram_title') }}</h2>
                        </div>
                        <button type="button" class="btn btn-ghost btn-sm ms-auto" data-bs-toggle="collapse"
                            data-bs-target="#telegram-help" aria-expanded="false">
                            <i class="ph-question"></i>{{ __('errorreport::errorreport.settings.how_to_get') }}
                        </button>
                    </div>
                    <div>
                        <div class="collapse" id="telegram-help">
                            <x-alert type="info">
                                <span class="fs-sm">{!! __('errorreport::errorreport.settings.telegram_help') !!}</span>
                            </x-alert>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <x-form.input
                                    name="error_report_telegram_bot_token"
                                    label="{{ __('errorreport::errorreport.settings.telegram_bot_token_label') }}"
                                    :value="optional($settings['error_report_telegram_bot_token'] ?? null)->value ?? ''"
                                    placeholder="{{ __('errorreport::errorreport.settings.telegram_bot_token_placeholder') }}"
                                />
                            </div>
                            <div class="col-md-6">
                                <x-form.input
                                    name="error_report_telegram_chat_id"
                                    label="{{ __('errorreport::errorreport.settings.telegram_chat_id_label') }}"
                                    :value="optional($settings['error_report_telegram_chat_id'] ?? null)->value ?? ''"
                                    placeholder="{{ __('errorreport::errorreport.settings.telegram_chat_id_placeholder') }}"
                                />
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Don't report --}}
                <div class="fd-form-section">
                    <div>
                        <h2 class="fd-form-section-title">{{ __('errorreport::errorreport.settings.ignore_title') }}</h2>
                        <p class="fd-form-section-text">{{ __('errorreport::errorreport.settings.ignore_help') }}</p>
                    </div>
                    <div>
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
                    </div>
                </div>

                <div class="d-flex justify-content-end mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="ph-floppy-disk"></i>{{ __('errorreport::errorreport.settings.save') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
