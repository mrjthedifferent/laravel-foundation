<?php

namespace Modules\Otp\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Otp\Enum\ContactType;

/**
 * Delivers the OTP code to the contact via the appropriate channel.
 *
 * The whitelist check (skip notification) lives in SendOtpAction — this
 * notification is never dispatched for whitelisted contacts.
 */
class SendVerificationCode extends Notification
{
    use Queueable;

    /**
     * The plain code to deliver. Only a hash is stored on the notifiable, so
     * the sender passes the code in; `$notifiable->code` is a fallback for
     * callers built against 1.7.
     */
    public function __construct(private readonly ?string $code = null) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return match ($notifiable->contact_type) {
            ContactType::Email => ['mail'],
            ContactType::Phone => ['sms'],
            default => [],
        };
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('otp::otp.notifications.subject'))
            ->view('otp::emails.verification-code', ['code' => $this->codeFor($notifiable)]);
    }

    public function toSms(object $notifiable): string
    {
        $appName = config('settings.app_name.value') ?: config('app.name');

        return __('otp::otp.notifications.sms_body', ['app' => $appName, 'code' => $this->codeFor($notifiable)]);
    }

    /**
     * What SMS logs record instead of the message: the same text with the
     * code masked, so codes never reach `sms_logs`.
     */
    public function toSmsLog(object $notifiable): string
    {
        $appName = config('settings.app_name.value') ?: config('app.name');
        $masked = str_repeat('*', max(4, strlen($this->codeFor($notifiable))));

        return __('otp::otp.notifications.sms_body', ['app' => $appName, 'code' => $masked]);
    }

    private function codeFor(object $notifiable): string
    {
        return (string) ($this->code ?? $notifiable->plainCode ?? $notifiable->code ?? '');
    }
}
