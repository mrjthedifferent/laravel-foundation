<?php

declare(strict_types=1);

namespace Mrj\Foundation\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * The current tenant was initialised or ended. Dispatched by the foundation
 * whenever one of foundation.tenancy.context_changed_events fires, so modules
 * listen to this one event instead of a tenancy library's.
 *
 * @api
 */
final class TenancyContextChanged
{
    use Dispatchable;
}
