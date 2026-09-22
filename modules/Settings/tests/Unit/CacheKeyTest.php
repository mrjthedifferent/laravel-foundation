<?php

declare(strict_types=1);

namespace Modules\Settings\Tests\Unit;

use Modules\Settings\Providers\SettingsServiceProvider;
use Tests\TestCase;

class CacheKeyTest extends TestCase
{
    public function test_it_has_no_prefix_by_default(): void
    {
        $this->assertSame('app_settings', SettingsServiceProvider::cacheKey());
    }

    public function test_a_configured_prefix_is_prepended(): void
    {
        config(['foundation.cache.prefix' => 'tenant_42:']);

        $this->assertSame('tenant_42:app_settings', SettingsServiceProvider::cacheKey());
    }
}
