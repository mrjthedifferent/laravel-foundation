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
            ->subject('Verification Code')
            ->view('otp::emails.verification-code', ['code' => $notifiable->code]);
    }

    public function toSms(object $notifiable): string
    {
        $appName = config('settings.app_name.value') ?: config('app.name');

        return "{$appName}: Your verification code is: {$notifiable->code}";
    }
}
