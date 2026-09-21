<?php

declare(strict_types=1);

namespace Mrj\Foundation\Tests\Unit;

use Mrj\Foundation\Support\Roles;
use Mrj\Foundation\Tests\TestCase;

class RolesTest extends TestCase
{
    public function test_it_defaults_to_the_packages_seeded_role_names(): void
    {
        $this->assertSame('Super Admin', Roles::superAdmin());
        $this->assertSame('Admin', Roles::admin());
        $this->assertSame('User', Roles::user());
    }

    public function test_it_reads_a_projects_configured_role_names(): void
    {
        config(['foundation.roles' => [
            'super_admin' => 'Owner',
            'admin' => 'Staff',
            'user' => 'Member',
        ]]);

        $this->assertSame('Owner', Roles::superAdmin());
        $this->assertSame('Staff', Roles::admin());
        $this->assertSame('Member', Roles::user());
    }
}
