<?php

namespace Modules\ActivityLog\Actions;

use Modules\ActivityLog\Models\EmailLog;

final readonly class DeleteEmailLogAction
{
    public function execute(int $id): void
    {
        EmailLog::findOrFail($id)->delete();
    }
}
