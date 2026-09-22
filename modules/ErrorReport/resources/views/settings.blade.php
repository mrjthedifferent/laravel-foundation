@extends('errorreport::layouts.master')

@section('breadcrumb')
    <span class="breadcrumb-item active">Settings</span>
@endsection

@section('content')
    <div class="card">
        <div class="card-header d-flex align-items-center gap-2 py-2">
            <div class="bg-primary bg-opacity-10 text-primary rounded d-flex align-items-center justify-content-center flex-shrink-0"
                style="width:32px;height:32px;">
                <i class="ph-gear"></i>
            </div>
            <div>
                <div class="fw-bold">Error Report Settings</div>
                <div class="text-muted fs-xs">Configure error capture, notification channels, and throttling</div>
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
                                    <label class="form-check-label fw-semibold" for="error_report_enabled">Enable error
                                        capture and notifications</label>
                                </div>
                                <div class="form-text">When enabled, uncaught exceptions are captured and sent via the
                                    configured channels.</div>
                            </div>
                        </div>
                    </div>

                    {{-- Channels --}}
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header py-2 bg-body-tertiary">
                                <span class="fw-bold fs-sm">Notification Channels</span>
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
                                    <label class="form-check-label" for="ch_mail">Email</label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" name="error_report_channels[]"
                                        id="ch_slack" value="slack" {{ in_array('slack', $channels) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="ch_slack">Slack</label>
                                </div>
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" name="error_report_channels[]"
                                        id="ch_telegram" value="telegram"
                                        {{ in_array('telegram', $channels) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="ch_telegram">Telegram</label>
                                </div>
                                <div class="form-text mt-1">Select which channels receive error notifications.</div>
                            </div>
                        </div>
                    </div>

                    {{-- Throttle --}}
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header py-2 bg-body-tertiary">
                                <span class="fw-bold fs-sm">Throttle</span>
                            </div>
                            <div class="card-body">
                                <x-form.input
                                    type="number"
                                    name="error_report_throttle_minutes"
                                    label="Minutes before re-notifying"
                                    :value="optional($settings['error_report_throttle_minutes'] ?? null)->value ?? 60"
                                    min="1"
                                    max="10080"
                                />
                                <div class="form-text">Same error will not be reported again within this period (max 10080 =
                                    1 week).</div>
                            </div>
                        </div>
                    </div>

                    {{-- Email recipients --}}
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header py-2 bg-body-tertiary">
                                <span class="fw-bold fs-sm">Email</span>
                            </div>
                            <div class="card-body">
                                <x-form.input
                                    name="error_report_email_recipients"
                                    label="Recipients (comma-separated)"
                                    :value="optional($settings['error_report_email_recipients'] ?? null)->value ?? ''"
                                    placeholder="admin@example.com, dev@example.com"
                                />
                                <div class="form-text">Fallback: development_support_email from General Settings.</div>
                            </div>
                        </div>
                    </div>

                    {{-- Slack --}}
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div
                                class="card-header py-2 bg-body-tertiary d-flex align-items-center justify-content-between">
                                <span class="fw-bold fs-sm">Slack</span>
                                <button type="button" class="btn btn-link btn-sm p-0 text-muted" data-bs-toggle="collapse"
                                    data-bs-target="#slack-help" aria-expanded="false">
                                    <i class="ph-question"></i> How to get
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="collapse" id="slack-help">
                                    <x-alert type="info">
                                        <span class="fs-sm"><strong>Webhook URL:</strong> Go to your Slack workspace → <a
                                            href="https://api.slack.com/apps" target="_blank"
                                            rel="noopener">api.slack.com/apps</a> → Create or select an app → Incoming
                                        Webhooks → Activate → Add New Webhook to Workspace → choose a channel → copy the
                                        webhook URL.</span>
                                    </x-alert>
                                </div>
                                <x-form.input
                                    type="url"
                                    name="error_report_slack_webhook"
                                    label="Webhook URL"
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
                                <span class="fw-bold fs-sm">Telegram</span>
                                <button type="button" class="btn btn-link btn-sm p-0 text-muted" data-bs-toggle="collapse"
                                    data-bs-target="#telegram-help" aria-expanded="false">
                                    <i class="ph-question"></i> How to get
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="collapse" id="telegram-help">
                                    <x-alert type="info">
                                        <span class="fs-sm"><strong>Bot Token:</strong> Open Telegram → search <code>@BotFather</code> → send
                                        <code>/newbot</code> → follow prompts → copy the token.<br>
                                        <strong>Chat ID:</strong> Start a chat with your bot (send <code>/start</code>).
                                        Visit <code>https://api.telegram.org/bot&lt;YOUR_TOKEN&gt;/getUpdates</code> and
                                        find <code>"chat":{"id":123456789}</code>. For groups, add the bot to the group
                                        first, then check getUpdates for the group chat ID (often negative).</span>
                                    </x-alert>
                                </div>
                                <x-form.input
                                    name="error_report_telegram_bot_token"
                                    label="Bot Token"
                                    :value="optional($settings['error_report_telegram_bot_token'] ?? null)->value ?? ''"
                                    placeholder="From @BotFather"
                                />
                                <x-form.label for="error_report_telegram_chat_id" class="mt-2">Chat ID</x-form.label>
                                <x-form.input
                                    name="error_report_telegram_chat_id"
                                    :value="optional($settings['error_report_telegram_chat_id'] ?? null)->value ?? ''"
                                    placeholder="Chat or group ID"
                                />
                            </div>
                        </div>
                    </div>

                    {{-- Don't report --}}
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header py-2 bg-body-tertiary">
                                <span class="fw-bold fs-sm">Exceptions to Ignore</span>
                            </div>
                            <div class="card-body">
                                @php $dontReport = optional($settings['error_report_dont_report'] ?? null)->value ?? []; @endphp
                                @php $dontReportStr = is_array($dontReport) ? implode("\n", $dontReport) : (is_string($dontReport) ? $dontReport : ''); @endphp
                                <x-form.textarea
                                    name="error_report_dont_report"
                                    label="Exception class names (one per line)"
                                    :value="$dontReportStr"
                                    class="font-monospace"
                                    :rows="5"
                                    placeholder="Illuminate\Auth\AuthenticationException&#10;Symfony\Component\HttpKernel\Exception\NotFoundHttpException"
                                />
                                <div class="form-text">These exceptions will not trigger notifications.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end mt-4">
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="ph-floppy-disk me-1"></i> Save Settings
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
