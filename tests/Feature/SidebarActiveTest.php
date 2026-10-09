<?php

namespace Mrj\Foundation\Tests\Feature;

use App\Models\User;
use Mrj\Foundation\Support\SidebarMenu;
use Mrj\Foundation\Tests\TestCase;

/**
 * Pages that share one route and differ only by its parameters (a generic resource
 * controller, say) are declared by `url`. The sidebar must still show which one is open.
 */
class SidebarActiveTest extends TestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->superAdmin()->create();
        config(['user.menu' => array_merge((array) config('user.menu'), [
            ['group' => 'administration', 'label' => 'Things', 'icon' => 'ph ph-cube', 'url' => 'admin/manage/things', 'order' => 90],
            ['group' => 'administration', 'label' => 'Thing types', 'icon' => 'ph ph-tag', 'url' => 'admin/manage/thing-types', 'order' => 91],
            ['group' => 'settings', 'label' => 'Thing settings', 'icon' => 'ph ph-gear', 'url' => 'admin/manage/things/settings', 'order' => 92],
            ['group' => 'settings', 'label' => 'Docs', 'icon' => 'ph ph-book', 'url' => 'https://docs.example.com/admin/manage/things', 'target' => '_blank', 'order' => 93],
        ])]);
    }

    /**
     * @return array<string, array{group: string, open: bool}> label => where it sits, for active items
     */
    private function active(?string $route, string $path): array
    {
        $active = [];
        foreach (app(SidebarMenu::class)->forUser($this->admin, $route, $path) as $group) {
            foreach ($group['items'] as $item) {
                if ($item['active']) {
                    $active[$item['label']] = ['group' => $group['key'], 'open' => $group['open']];
                }
            }
        }

        return $active;
    }

    public function test_a_url_item_is_active_on_its_page_and_the_pages_under_it(): void
    {
        foreach (['admin/manage/things', 'admin/manage/things/5', 'admin/manage/things/5/edit', 'admin/manage/things/create'] as $path) {
            $this->assertSame(['Things' => ['group' => 'administration', 'open' => true]], $this->active('admin.manage.index', $path), $path);
        }
    }

    public function test_a_similar_prefix_does_not_count(): void
    {
        $this->assertSame(['Thing types' => ['group' => 'administration', 'open' => true]], $this->active('admin.manage.index', 'admin/manage/thing-types/3'));
    }

    public function test_the_longest_matching_url_wins(): void
    {
        $this->assertSame(['Thing settings' => ['group' => 'settings', 'open' => true]], $this->active('admin.manage.show', 'admin/manage/things/settings'));
    }

    public function test_a_route_match_takes_precedence_and_other_hosts_never_match(): void
    {
        $active = $this->active('admin.users.index', 'admin/manage/things');

        $this->assertArrayHasKey('Users', $active);
        $this->assertArrayNotHasKey('Things', $active);
        $this->assertArrayNotHasKey('Docs', $this->active(null, 'admin/manage/things'));
    }

    public function test_nothing_is_active_on_an_unrelated_page(): void
    {
        $this->assertSame([], $this->active('admin.dashboard', 'admin/dashboard'));
    }
}
