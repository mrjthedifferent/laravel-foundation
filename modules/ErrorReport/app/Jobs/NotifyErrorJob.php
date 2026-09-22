<?php

namespace Modules\ErrorReport\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\ErrorReport\Models\ErrorReport;
use Modules\ErrorReport\Notifications\ErrorReportNotification;
use Throwable;

class NotifyErrorJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public ErrorReport $errorReport
    ) {}

    public function handle(): void
    {
        if (! $this->shouldNotify()) {
            return;
        }

        $channels = $this->getChannels();

        if (empty($channels)) {
            return;
        }

        $notification = new ErrorReportNotification($this->errorReport);

        foreach ($channels as $channel) {
            try {
                $this->sendToChannel($channel, $notification);
            } catch (Throwable $e) {
                Log::warning('Error report notification failed for channel', [
                    'channel' => $channel,
                    'error_report_id' => $this->errorReport->id,
                    'exception' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        $this->errorReport->update(['last_notified_at' => now()]);
    }

    private function shouldNotify(): bool
    {
        $throttleMinutes = (int) config('settings.error_report_throttle_minutes.value', 60);

        if ($throttleMinutes <= 0) {
            return true;
        }

        if ($this->errorReport->last_notified_at === null) {
            return true;
        }

        return $this->errorReport->last_notified_at->addMinutes($throttleMinutes)->isPast();
    }

    /**
     * @return array<int, string>
     */
    private function getChannels(): array
    {
        $channels = config('settings.error_report_channels.value', ['mail']);

        if (is_string($channels)) {
            $decoded = json_decode($channels, true);

            return is_array($decoded) ? $decoded : ['mail'];
        }

        return is_array($channels) ? $channels : ['mail'];
    }

    private function sendToChannel(string $channel, ErrorReportNotification $notification): void
    {
        $notifiable = new AnonymousNotifiable;

        match ($channel) {
            'mail' => $this->sendMail($notifiable, $notification),
            'slack' => $this->sendSlack($notifiable, $notification),
            'telegram' => $this->sendTelegram($notifiable, $notification),
            default => null,
        };
    }

    private function sendMail(AnonymousNotifiable $notifiable, ErrorReportNotification $notification): void
    {
        $recipients = $this->getMailRecipients();

        if (empty($recipients)) {
            return;
        }

        $notifiable->route('mail', $recipients)->notify($notification);
    }

    /**
     * @return array<int, string>
     */
    private function getMailRecipients(): array
    {
        $recipients = config('settings.error_report_email_recipients.value');

        if (! empty($recipients)) {
            return array_map('trim', explode(',', (string) $recipients));
        }

        $fallback = config('settings.development_support_email.value');

        return $fallback ? [trim((string) $fallback)] : [];
    }

    private function sendSlack(AnonymousNotifiable $notifiable, ErrorReportNotification $notification): void
    {
        $webhook = config('settings.error_report_slack_webhook.value') ?: config('logging.channels.slack.url');

        if (empty($webhook)) {
            return;
        }

        $notifiable->route('slack', $webhook)->notify($notification);
    }

    private function sendTelegram(AnonymousNotifiable $notifiable, ErrorReportNotification $notification): void
    {
        $chatId = config('settings.error_report_telegram_chat_id.value');
        $token = config('settings.error_report_telegram_bot_token.value');

        if (empty($chatId) || empty($token)) {
            return;
        }

        config(['services.telegram-bot-api.token' => $token]);

        $notifiable->route('telegram', $chatId)->notify($notification);
    }
}
