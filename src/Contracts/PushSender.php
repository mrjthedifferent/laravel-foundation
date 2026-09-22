<?php

declare(strict_types=1);

namespace Mrj\Foundation\Contracts;

/**
 * Bound in NotificationServiceProvider to FcmChannel. $notifiable/$notification
 * match Laravel's own notification-channel duck-typing convention (the
 * notification must implement toFcm($notifiable)).
 */
interface PushSender
{
    public function send(object $notifiable, object $notification): bool;
}
