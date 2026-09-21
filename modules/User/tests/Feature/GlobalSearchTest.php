<?php

namespace Modules\User\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class GlobalSearchTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Before escaping, a literal '%' in the query is a wildcard that matches
     * every row, turning a search box into a way to list every user's name
     * and email regardless of what was actually typed.
     */
    public function test_a_percent_sign_in_the_query_is_treated_literally_not_as_a_wildcard(): void
    {
        Permission::create(['name' => 'View User', 'guard_name' => 'web', 'module_name' => 'User']);
        $actor = User::factory()->create();
        $actor->givePermissionTo('View User');

        User::factory()->create(['name' => '100% Fitness']);
        User::factory()->create(['name' => 'Someone Else']);

        $response = $this->actingAs($actor)->getJson(route('admin.global-search', ['q' => '100%']));

        $response->assertOk();
        $data = $response->json('data');

        $this->assertCount(1, $data);
        $this->assertSame('100% Fitness', $data[0]['text']);
    }
}
