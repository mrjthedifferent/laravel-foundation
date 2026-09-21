<?php

namespace Modules\Notification\Tests\Unit\Jobs;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
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

        $captured = ['headers' => null, 'params' => null];

        $job = Mockery::mock(SendSmsJob::class, ['Hello there', '+8801711111111'])->makePartial();
        $job->shouldReceive('guzzle_post_call_json')
            ->once()
            ->andReturnUsing(function ($post, $url, $headers, $query) use (&$captured) {
                $captured['headers'] = $headers;
                $captured['params'] = $query;

                return [];
            });

        $job->handle();

        // The gateway must receive decrypted, not encrypted, values.
        $this->assertSame('Bearer tok', $captured['headers']['Authorization']);
        $this->assertSame('secret-key', $captured['params']['api_key']);
    }
}
