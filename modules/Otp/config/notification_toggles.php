<?php

declare(strict_types=1);

use Modules\Otp\Notifications\SendVerificationCode;

/*
 * Automatic notifications this module sends, for the Notification Settings page.
 * See Modules/Notification/app/Support/notification_toggles.php for the entry shape.
 */

return [
    ['type' => 'otp_verification', 'label' => 'OTP Verification Code', 'group' => 'Account & Security', 'class' => SendVerificationCode::class, 'channels' => ['mail', 'sms'], 'locked' => ['sms']],
];
