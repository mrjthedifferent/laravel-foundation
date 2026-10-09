<?php

namespace Mrj\Foundation\Tests\Unit;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Mrj\Foundation\Tests\TestCase;

class RateLimitConfigTest extends TestCase
{
    public function test_api_guest_limit_follows_config(): void
    {
        config(['foundation.rate_limits.api_guest' => 7]);

        $limits = RateLimiter::limiter('api')(Request::create('/api/x'));

        $this->assertSame(7, $limits->maxAttempts);
    }

    public function test_auth_ip_limit_follows_config(): void
    {
        config(['foundation.rate_limits.auth_ip' => 11]);

        $limits = RateLimiter::limiter('auth')(Request::create('/login', 'POST'));

        $this->assertSame(11, $limits[1]->maxAttempts);
    }

    public function test_defaults_match_the_previous_hard_coded_values(): void
    {
        $this->assertSame(30, config('foundation.rate_limits.auth_ip'));
        $this->assertSame(120, config('foundation.rate_limits.api_user'));
        $this->assertSame(30, config('foundation.rate_limits.api_guest'));
        $this->assertSame(191, config('foundation.schema.string_length'));
    }
}
