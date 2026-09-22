<?php

namespace Modules\User\Tests\Unit\Actions;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Modules\User\Actions\CreateUserAction;
use Modules\User\Data\UserData;
use Modules\User\Enum\Gender;
use Modules\User\Events\UserCreated;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CreateUserActionTest extends TestCase
{
    use RefreshDatabase;

    private CreateUserAction $action;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test role
        Role::create(['name' => 'tenant', 'guard_name' => 'web']);

        // Use Laravel's service container to resolve action with dependencies
        $this->action = app(CreateUserAction::class);
    }

    public function test_creates_user_successfully(): void
    {
        Event::fake([UserCreated::class]);

        // Prepare test data
        $userData = UserData::from([
            'email' => 'test@example.com',
            'phone' => '+1234567890',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'gender' => 'male',
            'roles' => [Role::where('name', 'tenant')->first()->id],
        ]);

        // Execute action
        $result = $this->action->execute($userData);

        // Assertions
        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals('test@example.com', $result->email);
        $this->assertTrue($result->hasRole('tenant'));

        Event::assertDispatched(UserCreated::class);
    }

    public function test_creates_profile_for_user(): void
    {
        Event::fake();

        $userData = UserData::from([
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'name' => 'Jane Smith',
            'gender' => 'female',
            'roles' => [Role::where('name', 'tenant')->first()->id],
        ]);

        $result = $this->action->execute($userData);

        // Profile fields are stored directly on the user model
        $this->assertEquals('Jane Smith', $result->name);
        $this->assertEquals(Gender::Female, $result->gender);
    }

    public function test_assigns_roles_to_user(): void
    {
        Event::fake();

        $role = Role::where('name', 'tenant')->first();

        $userData = UserData::from([
            'email' => 'test@example.com',
            'phone' => '+1234567890',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'gender' => 'male',
            'roles' => [$role->id],
        ]);

        $result = $this->action->execute($userData);

        $this->assertTrue($result->hasRole('tenant'));
        $this->assertCount(1, $result->roles);
    }
}
