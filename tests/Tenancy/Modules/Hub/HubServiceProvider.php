<?php

declare(strict_types=1);

namespace Mrj\Foundation\Tests\Tenancy\Modules\Hub;

use Mrj\Foundation\Support\ModuleServiceProvider;

final class HubServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Hub';

    protected string $nameLower = 'hub';
}
