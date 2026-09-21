<?php

namespace Modules\ActivityLog\Actions;

use Modules\ActivityLog\Models\SmsLog;

final readonly class DeleteSmsLogAction
{
    public function execute(int $id): void
    {
        SmsLog::findOrFail($id)->delete();
    }
}
