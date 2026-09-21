<?php

namespace Mrj\Foundation\Tests\Feature;

use App\Models\User;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Schema;
use Mrj\Foundation\Exceptions\Handler;
use Mrj\Foundation\Foundation;
use Mrj\Foundation\Http\Middleware\CheckUserIsActive;
use Mrj\Foundation\Http\Middleware\EnsureContactIsVerified;
use Mrj\Foundation\Http\Middleware\SlideSanctumTokenExpiry;
use Mrj\Foundation\Tests\TestCase;

class BootstrapTest extends TestCase
{
    public function test_middleware_defaults_are_applied_before_the_projects_own(): void
    {
        $middleware = new Middleware;

        (Foundation::middleware(function (Middleware $m): void {
            $m->alias(['project' => 'ProjectMiddleware']);
        }))($middleware);

        $groups = $middleware->getMiddlewareGroups();
        $aliases = $middleware->getMiddlewareAliases();

        $this->assertContains(CheckUserIsActive::class, $groups['web']);
        $this->assertSame(
            [CheckUserIsActive::class, SlideSanctumTokenExpiry::class],
            array_slice($groups['api'], -2),
        );
        $this->assertSame(EnsureContactIsVerified::class, $aliases['verified']);
        $this->assertSame('ProjectMiddleware', $aliases['project']);
    }

    public function test_singletons_bind_the_foundation_exception_handler(): void
    {
        $this->assertSame([ExceptionHandler::class => Handler::class], Foundation::singletons());
    }

    public function test_user_morph_alias_points_at_the_projects_model(): void
    {
        $this->assertSame(User::class, Relation::getMorphedModel('user'));
        $this->assertTrue(Relation::requiresMorphMap());
    }

    public function test_base_migrations_run(): void
    {
        foreach (['users', 'sessions', 'cache', 'jobs', 'roles', 'permissions', 'personal_access_tokens', 'audits', 'device_tokens'] as $table) {
            $this->assertTrue(Schema::hasTable($table), "$table table is missing");
        }

        $this->assertTrue(Schema::hasColumns('users', ['uuid', 'name', 'is_active', 'phone', 'phone_verified_at']));
    }
}
