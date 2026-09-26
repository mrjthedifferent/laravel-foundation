<?php

declare(strict_types=1);

namespace Mrj\Foundation\Tests\Tenancy\Modules\Ledger;

use Mrj\Foundation\Support\ModuleServiceProvider;

final class LedgerServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Ledger';

    protected string $nameLower = 'ledger';
}
