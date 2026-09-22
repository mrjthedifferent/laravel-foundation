<?php

declare(strict_types=1);

namespace Mrj\Foundation\Contracts;

/**
 * Bound in NotificationServiceProvider to SmsGatewayManager, which picks
 * between a log (dry-run) driver and a generic-HTTP driver based on the
 * currently configured sms_gateway setting.
 *
 * @api
 */
interface SmsGateway
{
    public function send(string $phone, string $message): void;
}
