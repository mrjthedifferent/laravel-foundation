<?php

declare(strict_types=1);

namespace Modules\Otp\Tests\Unit\Notifications;

use Modules\Otp\Notifications\SendVerificationCode;
use Tests\TestCase;

class SendVerificationCodeTest extends TestCase
{
    public function test_sms_message_is_prefixed_with_app_name_from_settings(): void
    {
        config(['settings.app_name.value' => 'Acme Portal']);

        $message = (new SendVerificationCode)->toSms((object) ['code' => '123456']);

        $this->assertSame('Acme Portal: Your verification code is: 123456', $message);
    }

    public function test_sms_uses_the_code_passed_in_and_logs_it_masked(): void
    {
        config(['settings.app_name.value' => 'Acme']);
        $notification = new SendVerificationCode('482913');
        $notifiable = (object) ['code' => null];

        $this->assertSame('Acme: Your verification code is: 482913', $notification->toSms($notifiable));
        $this->assertSame('Acme: Your verification code is: ******', $notification->toSmsLog($notifiable));
    }

    public function test_sms_message_falls_back_to_app_config_name(): void
    {
        config(['settings.app_name.value' => null, 'app.name' => 'Acme']);

        $message = (new SendVerificationCode)->toSms((object) ['code' => '654321']);

        $this->assertSame('Acme: Your verification code is: 654321', $message);
    }
}
