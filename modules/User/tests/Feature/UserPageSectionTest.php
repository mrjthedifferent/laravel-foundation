<?php

namespace Modules\User\Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\HtmlString;
use Mrj\Foundation\Foundation;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Foundation::userPageSection(): projects add their own sections to the admin user page,
 * below the main details.
 */
class UserPageSectionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $member;

    protected function setUp(): void
    {
        parent::setUp();

        Permission::updateOrCreate(['name' => 'View User', 'guard_name' => 'web'], ['module_name' => 'User']);
        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->givePermissionTo('View User');
        $this->member = User::factory()->create(['name' => 'Viewed Member']);
    }

    protected function tearDown(): void
    {
        Foundation::flushUserPageSections();

        parent::tearDown();
    }

    private function page(): string
    {
        return $this->actingAs($this->admin)
            ->get(route('admin.users.show', $this->member))
            ->assertOk()
            ->getContent();
    }

    public function test_with_nothing_registered_the_page_has_no_sections(): void
    {
        $html = $this->page();

        $this->assertStringContainsString('Viewed Member', $html);
        $this->assertStringNotContainsString('data-user-section', $html);
    }

    public function test_a_registered_section_renders_below_the_details(): void
    {
        Foundation::userPageSection('extra', fn () => new HtmlString('<p>Extra section body</p>'));

        $html = $this->page();

        $this->assertStringContainsString('<div class="mt-4" data-user-section="extra"><p>Extra section body</p></div>', $html);
        $this->assertGreaterThan(strpos($html, 'id="user-tab-content"'), strpos($html, 'Extra section body'));
    }

    public function test_the_resolver_gets_the_viewed_user_and_the_viewer(): void
    {
        $received = [];
        Foundation::userPageSection('who', function (User $user, User $viewer) use (&$received): string {
            $received = [$user->id, $viewer->id];

            return '<p>Viewing '.e($user->name).' as '.e($viewer->name).'</p>';
        });

        $html = $this->page();

        $this->assertSame([$this->member->id, $this->admin->id], $received);
        $this->assertStringContainsString('<p>Viewing Viewed Member as '.e($this->admin->name).'</p>', $html);
    }

    public function test_a_resolver_returning_null_renders_nothing(): void
    {
        Foundation::userPageSection('hidden', fn () => null);

        $this->assertStringNotContainsString('data-user-section', $this->page());
    }

    public function test_sections_render_by_order_then_registration(): void
    {
        Foundation::userPageSection('late', fn () => '<p>Section C</p>', 200);
        Foundation::userPageSection('first', fn () => '<p>Section A</p>', 10);
        Foundation::userPageSection('default', fn () => '<p>Section B</p>');
        Foundation::userPageSection('tie', fn () => '<p>Section B2</p>');

        $this->assertSeeInOrderHtml($this->page(), ['Section A', 'Section B', 'Section B2', 'Section C']);
    }

    public function test_a_view_is_rendered(): void
    {
        Foundation::userPageSection('view', fn (User $user) => view()->file(__DIR__.'/fixtures/user-section.blade.php', ['name' => $user->name]));

        $this->assertStringContainsString('<p>View for Viewed Member</p>', $this->page());
    }

    /**
     * @param  list<string>  $needles
     */
    private function assertSeeInOrderHtml(string $html, array $needles): void
    {
        $position = -1;
        foreach ($needles as $needle) {
            $found = strpos($html, $needle, $position + 1);
            $this->assertNotFalse($found, "'{$needle}' not found in order.");
            $position = $found;
        }
    }
}
