<?php

declare(strict_types=1);

namespace Modules\ActivityLog\Listeners;

use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\ActivityLog\Actions\CreateEmailLogAction;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Throwable;

/**
 * Logs every outgoing email at the moment it is handed to the transport.
 *
 * A correlation UUID is stamped onto the message as the "X-Email-Log-Uuid"
 * header so the matching MessageSent event can flip the row to "sent".
 * Any failure here is swallowed (and logged to the daily_email channel) so
 * that logging can never break email delivery.
 */
class LogEmailSending
{
    public function __construct(private readonly CreateEmailLogAction $action) {}

    public function handle(MessageSending $event): void
    {
        try {
            $message = $event->message;

            if (! $message instanceof Email) {
                return;
            }

            $uuid = (string) Str::uuid();
            $message->getHeaders()->addTextHeader('X-Email-Log-Uuid', $uuid);

            $to = $this->firstAddress($message->getTo());

            $emailLog = $this->action->execute([
                'uuid' => $uuid,
                'to_email' => $to['email'] ?? 'unknown',
                'to_name' => $to['name'] ?? null,
                'cc' => $this->addressEmails($message->getCc()),
                'bcc' => $this->addressEmails($message->getBcc()),
                'from_email' => $this->firstAddress($message->getFrom())['email'] ?? null,
                'from_name' => $this->firstAddress($message->getFrom())['name'] ?? null,
                'subject' => $message->getSubject(),
                'body' => $message->getHtmlBody() ?: $message->getTextBody(),
                'mailer' => $event->data['__laravel_mailer'] ?? config('mail.default'),
                'notification' => isset($event->data['__laravel_notification'])
                    ? (is_object($event->data['__laravel_notification'])
                        ? $event->data['__laravel_notification']::class
                        : (string) $event->data['__laravel_notification'])
                    : null,
                'status' => 'pending',
                'metadata' => [
                    'recipients' => $this->addressEmails($message->getTo()),
                ],
            ]);

            Log::channel('daily_email')->info('Email sending', [
                'id' => $emailLog->id,
                'uuid' => $emailLog->uuid,
                'to' => $emailLog->to_email,
                'subject' => $emailLog->subject,
                'mailer' => $emailLog->mailer,
                'notification' => $emailLog->notification,
            ]);
        } catch (Throwable $e) {
            Log::channel('daily_email')->warning('Failed to record outgoing email', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @param  array<int, Address>  $addresses
     * @return array{email: string, name: string|null}|array{}
     */
    private function firstAddress(array $addresses): array
    {
        $address = $addresses[0] ?? null;

        if (! $address instanceof Address) {
            return [];
        }

        return [
            'email' => $address->getAddress(),
            'name' => $address->getName() ?: null,
        ];
    }

    /**
     * @param  array<int, Address>  $addresses
     * @return array<int, string>|null
     */
    private function addressEmails(array $addresses): ?array
    {
        if ($addresses === []) {
            return null;
        }

        return array_map(static fn (Address $address): string => $address->getAddress(), $addresses);
    }
}
