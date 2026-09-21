<?php

namespace Modules\ActivityLog\Actions;

use Modules\ActivityLog\Models\EmailLog;

final readonly class CreateEmailLogAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data): EmailLog
    {
        return EmailLog::create($data);
    }
}
