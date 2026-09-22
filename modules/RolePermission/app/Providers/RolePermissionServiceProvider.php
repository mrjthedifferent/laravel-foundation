<?php

declare(strict_types=1);

namespace Modules\RolePermission\Providers;

use Modules\RolePermission\Policies\RolePolicy;
use Modules\RolePermission\View\Composers\RolePermissionWidgetComposer;
use Mrj\Foundation\Support\ModuleServiceProvider;
use Spatie\Permission\Models\Role;

class RolePermissionServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'RolePermission';

    protected string $nameLower = 'rolepermission';

    protected array $policies = [
        Role::class => RolePolicy::class,
    ];

    protected array $composers = [
        'rolepermission::partials.dashboard-widget' => RolePermissionWidgetComposer::class,
    ];
}
