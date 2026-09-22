<?php

namespace Modules\BackupCleanup\Tests\Feature;

use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

/**
 * routes/console.php declared these schedules, but nothing ever loaded that
 * file — the module's RouteServiceProvider only mapped web/api routes, and
 * nwidart/laravel-modules does not auto-load routes/console.php on its own.
 * The backup cleanup and old-notification pruning commands never ran in any
 * installation of this package. ModuleServiceProvider::loadRoutes() now loads
 * routes/console.php by the same convention as web.php/api.php.
 */
class ScheduledCommandsTest extends TestCase
{
    public function test_the_backup_cleanup_and_notification_pruning_commands_are_scheduled(): void
    {
        $commands = array_map(
            fn ($event) => $event->command,
            app(Schedule::class)->events()
        );

        $this->assertTrue(
            (bool) array_filter($commands, fn ($command) => str_contains((string) $command, 'system:backup:cleanup')),
            'system:backup:cleanup is not scheduled'
        );

        $this->assertTrue(
            (bool) array_filter($commands, fn ($command) => str_contains((string) $command, 'clear:old-notification')),
            'clear:old-notification is not scheduled'
        );
    }
}
