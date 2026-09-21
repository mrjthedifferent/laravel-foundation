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
            ->subject('[Error] '.config('app.name').' - '.Str::limit($report->message, 60))
            ->line($summary)
            ->line('**Exception:** '.$report->exception_class)
            ->line('**File:** '.$report->file.':'.$report->line)
            ->line('**Occurrences:** '.$report->occurrences)
            ->line('**First seen:** '.$report->first_seen_at->format('Y-m-d H:i:s'))
            ->line('**Last seen:** '.$report->last_seen_at->format('Y-m-d H:i:s'))
            ->when($report->request_url, fn (MailMessage $m) => $m->line('**URL:** '.$report->request_url))
            ->action('View in Dashboard', route('admin.error-reports.show', $report));
    }

    public function toSlack(object $notifiable): SlackMessage
    {
        $report = $this->errorReport;
        $summary = $this->buildSummary();

        return (new SlackMessage)
            ->error()
            ->content('*'.config('app.name').' Error Report*')
            ->attachment(function ($attachment) use ($report): void {
                $attachment
                    ->title($report->exception_class, route('admin.error-reports.show', $report))
                    ->fields([
                        'Message' => Str::limit($report->message, 200),
                        'File' => $report->file.':'.$report->line,
                        'Occurrences' => (string) $report->occurrences,
                        'URL' => $report->request_url ?? '—',
                    ]);
            });
    }

    public function toTelegram(object $notifiable): TelegramMessage
    {
        $report = $this->errorReport;
        $summary = $this->buildSummary();

        $text = '*'.config('app.name')." Error*\n\n";
        $text .= $summary."\n\n";
        $text .= 'Exception: `'.$report->exception_class."`\n";
        $text .= 'File: `'.$report->file.':'.$report->line."`\n";
        $text .= 'Occurrences: '.$report->occurrences;

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
