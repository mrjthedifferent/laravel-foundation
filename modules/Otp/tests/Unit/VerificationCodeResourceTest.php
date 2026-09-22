<?php

declare(strict_types=1);

namespace Modules\Otp\Tests\Unit;

use Illuminate\Http\Request;
use Modules\Otp\Http\Resources\VerificationCodeResource;
use Modules\Otp\Models\VerificationCode;
use Tests\TestCase;

class VerificationCodeResourceTest extends TestCase
{
    public function test_expires_at_is_serialized_with_the_app_timezone_offset(): void
    {
        config(['app.timezone' => 'Asia/Dhaka']);
        date_default_timezone_set('Asia/Dhaka');

        $code = new VerificationCode([
            'code' => '123456',
            'contact_type' => 'email',
            'contact' => 'user@example.com',
            'expires_at' => '2026-09-21 11:36:03',
        ]);

        $payload = (new VerificationCodeResource($code))->toArray(Request::create('/'));

        $this->assertSame('2026-09-21T11:36:03+06:00', $payload['expires_at']);
    }

    public function test_expires_at_is_null_when_the_code_has_no_expiry(): void
    {
        $code = new VerificationCode([
            'code' => '123456',
            'contact_type' => 'email',
            'contact' => 'user@example.com',
        ]);

        $payload = (new VerificationCodeResource($code))->toArray(Request::create('/'));

        $this->assertNull($payload['expires_at']);
    }
}
