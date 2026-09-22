<?php

declare(strict_types=1);

namespace Modules\BackupCleanup\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\BackupCleanup\Actions\RunCleanupAction;

class RunCleanupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 120;

    public function handle(RunCleanupAction $action): void
    {
        $action->execute();
    }
}
