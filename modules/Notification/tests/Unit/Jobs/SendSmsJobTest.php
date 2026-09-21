<?php

namespace Modules\Notification\Tests\Unit\Jobs;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Modules\Notification\Jobs\SendSmsJob;
use Modules\Settings\Models\Setting;
use Modules\Settings\Services\MailerSecretCipher;
use Tests\TestCase;

class SendSmsJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_decrypts_header_and_param_values_before_calling_the_gateway(): void
    {
        $cipher = new MailerSecretCipher;

        Setting::updateOrCreate(
            ['key' => 'sms_gateways'],
            ['type' => 'json', 'group' => 'General', 'is_visible' => false, 'value' => json_encode([
                ['TYPE' => 'Provider', 'VALUE' => [
                    'endpoint' => 'https://api.provider.com/send',
                    'method' => 'POST',
                    'mobile_prefix' => null,
                    'mobile_key' => 'mobile',
                    'message_key' => 'text',
                    'headers' => ['Authorization' => $cipher->encrypt('Bearer tok')],
                    'params' => ['api_key' => $cipher->encrypt('secret-key')],
                ]],
            ])]
        );
        Setting::updateOrCreate(
            ['key' => 'sms_gateway'],
            ['type' => 'select', 'group' => 'General', 'is_visible' => false, 'value' => 'Provider']
        );

        Http::fake(['api.provider.com/*' => Http::response([], 200)]);

        (new SendSmsJob('Hello there', '+8801711111111'))->handle();

        Http::assertSent(function ($request) {
            // The gateway must receive decrypted, not encrypted, values. The job
            // strips the leading '+' before building params (gateways expect the
            // country code with no plus sign).
            return $request->url() === 'https://api.provider.com/send'
                && $request->hasHeader('Authorization', 'Bearer tok')
                && $request['api_key'] === 'secret-key'
                && $request['mobile'] === '8801711111111'
                && $request['text'] === 'Hello there';
        });
    }
}
