<?php

namespace Modules\Notification\Channels;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Modules\Notification\Jobs\SendSmsJob;
use Mrj\Foundation\Contracts\HasSmsContact;

class SmsChannel
{
    public function send(object $notifiable, object $notification): bool
    {
        if (! method_exists($notification, 'toSms')) {
            return false;
        }

        $message = $notification->toSms($notifiable);

        $contact = match (true) {
            $notifiable instanceof HasSmsContact => $notifiable->getSmsContact(),
            $notifiable instanceof User => $notifiable->phone ?? null,
            default => null,
        };

        if (empty($contact)) {
            Log::channel('daily_sms')->error('No contact information available for SMS notification.');

            return true;
        }

        SendSmsJob::dispatch($message, $contact);

        return true;
    }
}
