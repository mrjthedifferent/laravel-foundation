## Module Tests

### Unit Tests (Actions)

Located in `Modules/{Name}/tests/Unit/Actions/`. Test the action in isolation.

```php
class CreateThingActionTest extends TestCase
{
    use RefreshDatabase;

    private CreateThingAction $action;

    protected function setUp(): void
    {
        parent::setUp();
        $this->action = app(CreateThingAction::class); // resolve via container for DI
    }

    public function test_creates_thing_successfully(): void
    {
        Event::fake([ThingCreated::class]);

        $result = $this->action->execute(ThingData::from([
            'name'      => 'Test Thing',
            'is_active' => true,
        ]));

        $this->assertInstanceOf(Thing::class, $result);
        $this->assertEquals('Test Thing', $result->name);
        Event::assertDispatched(ThingCreated::class);
    }
}
```

### Feature Tests (Controllers)

Located in `Modules/{Name}/tests/Feature/`. Test the full HTTP request cycle including policy and validation.

```php
class ThingControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['is_active' => true]);
        $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin']);
        $role->givePermissionTo(
            \Spatie\Permission\Models\Permission::firstOrCreate(
                ['name' => 'Create Thing'],
                ['module_name' => 'Thing Management', 'guard_name' => 'web']
            )
        );
        $this->admin->assignRole($role);
    }

    public function test_authorized_user_can_create_thing(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.things.store'), ['name' => 'New Thing', 'is_active' => true]);

        $response->assertRedirect(route('admin.things.index'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('things', ['name' => 'New Thing']);
    }

    public function test_unauthorized_user_cannot_create_thing(): void
    {
        $this->actingAs(User::factory()->create())
            ->post(route('admin.things.store'), ['name' => 'New Thing'])
            ->assertForbidden();
    }

    public function test_validation_fails_without_required_fields(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.things.store'), [])
            ->assertSessionHasErrors(['name']);
    }
}
```

### Test Rules

- Only write tests when the user explicitly asks for it.
- When writing controller tests, cover: **happy path**, **unauthorized access (403)**, and **validation failure**.
- Use `User::factory()->create()` and `Role::firstOrCreate()` — never manually insert via DB.
- Use `Storage::fake('public')` for file upload tests.
- Prefer `assertSessionHas('success')` and `assertSessionHas('error')` over asserting exact flash messages.
- Use `assertDatabaseHas()` / `assertDatabaseMissing()` to verify persistence.
- Use `Event::fake([SpecificEvent::class])` to prevent real event side-effects in unit tests.
- Use `\Illuminate\Foundation\Http\Middleware\PreventRequestForgery::class` (renamed from `ValidateCsrfToken` in Laravel 13) in `$this->withoutMiddleware()` for web form tests.
