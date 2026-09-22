<?php

namespace Modules\ErrorReport\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\SlackMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;
use Modules\ErrorReport\Models\ErrorReport;
use NotificationChannels\Telegram\TelegramMessage;

class ErrorReportNotification extends Notification
{
    use Queueable;

    public function __construct(
        public ErrorReport $errorReport
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = [];

        if ($notifiable->routeNotificationFor('mail')) {
            $channels[] = 'mail';
        }

        if ($notifiable->routeNotificationFor('slack')) {
            $channels[] = 'slack';
        }

        if ($notifiable->routeNotificationFor('telegram')) {
            $channels[] = 'telegram';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $report = $this->errorReport;
        $summary = $this->buildSummary();

        return (new MailMessage)
            ->subject(__('errorreport::errorreport.notification.mail_subject', [
                'app' => config('app.name'),
                'message' => Str::limit($report->message, 60),
            ]))
            ->line($summary)
            ->line(__('errorreport::errorreport.notification.exception_line', ['value' => $report->exception_class]))
            ->line(__('errorreport::errorreport.notification.file_line', ['value' => $report->file.':'.$report->line]))
            ->line(__('errorreport::errorreport.notification.occurrences_line', ['value' => $report->occurrences]))
            ->line(__('errorreport::errorreport.notification.first_seen_line', ['value' => $report->first_seen_at->format(config('foundation.formats.datetime'))]))
            ->line(__('errorreport::errorreport.notification.last_seen_line', ['value' => $report->last_seen_at->format(config('foundation.formats.datetime'))]))
            ->when($report->request_url, fn (MailMessage $m) => $m->line(__('errorreport::errorreport.notification.url_line', ['value' => $report->request_url])))
            ->action(__('errorreport::errorreport.notification.view_in_dashboard'), route('admin.error-reports.show', $report));
    }

    public function toSlack(object $notifiable): SlackMessage
    {
        $report = $this->errorReport;
        $summary = $this->buildSummary();

        return (new SlackMessage)
            ->error()
            ->content(__('errorreport::errorreport.notification.slack_title', ['app' => config('app.name')]))
            ->attachment(function ($attachment) use ($report): void {
                $attachment
                    ->title($report->exception_class, route('admin.error-reports.show', $report))
                    ->fields([
                        __('errorreport::errorreport.notification.slack_field_message') => Str::limit($report->message, 200),
                        __('errorreport::errorreport.notification.slack_field_file') => $report->file.':'.$report->line,
                        __('errorreport::errorreport.notification.slack_field_occurrences') => (string) $report->occurrences,
                        __('errorreport::errorreport.notification.slack_field_url') => $report->request_url ?? '—',
                    ]);
            });
    }

    public function toTelegram(object $notifiable): TelegramMessage
    {
        $report = $this->errorReport;
        $summary = $this->buildSummary();

        $text = __('errorreport::errorreport.notification.telegram_title', ['app' => config('app.name')])."\n\n";
        $text .= $summary."\n\n";
        $text .= __('errorreport::errorreport.notification.telegram_exception', ['value' => $report->exception_class])."\n";
        $text .= __('errorreport::errorreport.notification.telegram_file', ['value' => $report->file.':'.$report->line])."\n";
        $text .= __('errorreport::errorreport.notification.telegram_occurrences', ['value' => $report->occurrences]);

        return TelegramMessage::create()
            ->content($text)
            ->options(['parse_mode' => 'Markdown']);
    }

    private function buildSummary(): string
    {
        $report = $this->errorReport;

        return Str::limit($report->message, 300);
    }
}
