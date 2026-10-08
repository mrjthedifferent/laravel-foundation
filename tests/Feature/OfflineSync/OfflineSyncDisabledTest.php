<?php

declare(strict_types=1);

namespace Mrj\Foundation\Tests\Feature\OfflineSync;

use App\Models\User;
use Mrj\Foundation\Tests\TestCase;

class OfflineSyncDisabledTest extends TestCase
{
    public function test_sync_routes_do_not_exist_until_a_handler_is_configured(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->actingAs($user, 'sanctum')->getJson('/api/v1/sync/pull')->assertNotFound();
        $this->actingAs($user, 'sanctum')->postJson('/api/v1/sync/push', ['ops' => []])->assertNotFound();
    }
}
