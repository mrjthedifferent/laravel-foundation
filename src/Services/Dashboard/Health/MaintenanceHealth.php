<?php

declare(strict_types=1);

namespace Mrj\Foundation\Services\Dashboard\Health;

use Mrj\Foundation\Support\HealthCheck;
use Override;

/**
 * Whether the application is switched to maintenance mode, which locks everyone but
 * super admins out. Worth a line of its own, because it is easy to leave on.
 *
 * @internal
 */
final class MaintenanceHealth extends HealthCheck
{
    #[Override]
    public function permissions(): array
    {
        return ['View Logs', 'Developer Setting', 'Edit System Setting'];
    }

    #[Override]
    public function priority(): int
    {
        return 40;
    }

    #[Override]
    public function check(): array
    {
        $on = app()->isDownForMaintenance();

        return [
            'status' => $on ? self::WARN : self::OK,
            'label' => __('foundation::foundation.dashboard.health_maintenance'),
            'detail' => $on
                ? __('foundation::foundation.dashboard.health_maintenance_on')
                : __('foundation::foundation.dashboard.health_maintenance_off'),
        ];
    }
}
