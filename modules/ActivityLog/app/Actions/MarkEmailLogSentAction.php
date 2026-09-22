<?php

namespace Modules\ActivityLog\Actions;

use Modules\ActivityLog\Models\EmailLog;

final readonly class MarkEmailLogSentAction
{
    public function execute(string $uuid): ?EmailLog
    {
        $emailLog = EmailLog::query()->where('uuid', $uuid)->first();

        if ($emailLog === null) {
            return null;
        }

        $emailLog->update([
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        return $emailLog;
    }
}
