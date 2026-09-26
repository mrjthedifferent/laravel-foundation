<?php

declare(strict_types=1);

namespace Modules\ActivityLog\Tests\Unit;

use Modules\ActivityLog\Http\Middleware\LogRequestResponse;
use Mrj\Foundation\Tests\TestCase;
use ReflectionMethod;

class SensitiveKeysTest extends TestCase
{
    /**
     * @param  array<mixed>  $payload
     * @return array<mixed>
     */
    private function sanitize(array $payload): array
    {
        $method = new ReflectionMethod(LogRequestResponse::class, 'sanitizePayload');

        return $method->invoke(app(LogRequestResponse::class), $payload);
    }

    public function test_project_keys_are_masked_at_any_depth_alongside_the_built_in_ones(): void
    {
        config(['activitylog.sensitive_keys' => ['msisdn', 'Credentials']]);

        $clean = $this->sanitize([
            'msisdn' => '01712345678',
            'amount' => 100,
            'password' => 'hunter2',
            'items' => [['MSISDN' => '01812345678', 'note' => 'kept']],
            'credentials' => ['app_secret' => 'x', 'app_key' => 'y'],
        ]);

        $this->assertSame('***********', $clean['msisdn']);
        $this->assertSame(100, $clean['amount']);
        $this->assertSame('*******', $clean['password']);
        $this->assertSame('***********', $clean['items'][0]['MSISDN']);
        $this->assertSame('kept', $clean['items'][0]['note']);
        $this->assertSame('***', $clean['credentials']);
    }

    public function test_nothing_extra_is_masked_by_default(): void
    {
        $this->assertSame('01712345678', $this->sanitize(['msisdn' => '01712345678'])['msisdn']);
    }
}
