<?php

declare(strict_types=1);

namespace Mrj\Foundation\Contracts;

/**
 * Lets a notifiable declare its SMS contact address without Notification's
 * SmsChannel needing to import the concrete class (e.g. Otp's
 * VerificationCode, an optional module) to check `instanceof`.
 *
 * @api
 */
interface HasSmsContact
{
    public function getSmsContact(): ?string;
}
