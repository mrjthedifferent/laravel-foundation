<?php

namespace Modules\ActivityLog\Listeners;

use Illuminate\Mail\Events\MessageSent;
use Illuminate\Support\Facades\Log;
use Modules\ActivityLog\Actions\MarkEmailLogSentAction;
use Throwable;

/**
 * Flips the EmailLog row created by LogEmailSending to "sent" once the
 * transport confirms delivery, matched via the "X-Email-Log-Uuid" header.
 */
class LogEmailSent
{
    public function __construct(private readonly MarkEmailLogSentAction $action) {}

    public function handle(MessageSent $event): void
    {
        try {
            $header = $event->sent->getOriginalMessage()
                ->getHeaders()
                ->get('X-Email-Log-Uuid');

            if ($header === null) {
                return;
            }

            $emailLog = $this->action->execute($header->getBodyAsString());

            if ($emailLog !== null) {
                Log::channel('daily_email')->info('Email sent', [
                    'id' => $emailLog->id,
                    'uuid' => $emailLog->uuid,
                    'to' => $emailLog->to_email,
                    'subject' => $emailLog->subject,
                    'mailer' => $emailLog->mailer,
                    'notification' => $emailLog->notification,
                ]);
            }
        } catch (Throwable $e) {
            Log::channel('daily_email')->warning('Failed to mark email as sent', [
                'error' => $e->getMessage(),
            ]);
        }
    }
}
