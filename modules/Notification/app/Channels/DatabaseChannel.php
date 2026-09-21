<?php

namespace Modules\Notification\Channels;

use Modules\Notification\Enum\NotificationType;
use Modules\Notification\Models\Notification;

class DatabaseChannel
{
    public function send(object $notifiable, object $notification): void
    {
        $data = $this->payload($notifiable, $notification);

        if ($data === null) {
            return;
        }

        $type = $notification->type ?? NotificationType::Info;

        Notification::create([
            'notifiable_type' => get_class($notifiable),
            'notifiable_id' => $notifiable->id,
            'type' => $type instanceof NotificationType ? $type->value : (string) $type,
            'data' => $data,
        ]);
    }

    /**
     * toDatabase() when the notification defines one, otherwise toArray() — the same fallback
     * Laravel's own database channel uses.
     *
     * Requiring toDatabase() silently dropped every notification that only defines toArray():
     * eight registered types (Missing Attendance, Absenteeism Alert, Monthly Late Deduction and
     * others) declared the database channel and exposed an in-app toggle, but never stored a row.
     *
     * @return array<string, mixed>|null
     */
    private function payload(object $notifiable, object $notification): ?array
    {
        if (method_exists($notification, 'toDatabase')) {
            return $notification->toDatabase($notifiable);
        }

        if (method_exists($notification, 'toArray')) {
            return $notification->toArray($notifiable);
        }

        return null;
    }
}
